<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Doctrine\EventListener;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\GraphQl\Subscription\MercureSubscriptionIriGeneratorInterface as GraphQlMercureSubscriptionIriGeneratorInterface;
use ApiPlatform\GraphQl\Subscription\SubscriptionManagerInterface as GraphQlSubscriptionManagerInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use ApiPlatform\Symfony\Messenger\DispatchTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs as MongoDbOdmOnFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs as OrmOnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Publishes resources updates to the Mercure hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PublishMercureUpdatesListener
{
    use DispatchTrait;
    use ResourceClassInfoTrait;
    private const ALLOWED_KEYS = [
        'topics' => true,
        'data' => true,
        'private' => true,
        'id' => true,
        'type' => true,
        'retry' => true,
        'normalization_context' => true,
        'hub' => true,
        'enable_async_update' => true,
    ];
    private readonly ?ExpressionLanguage $expressionLanguage;
    private \SplObjectStorage $createdObjects;
    private \SplObjectStorage $updatedObjects;
    private \SplObjectStorage $deletedObjects;
    /**
     * GraphQL subscriptions only
     *
     * @var \SplObjectStorage
     */
    private \SplObjectStorage $subscribedToObjects;

    /**
     * @param array<string, string[]|string> $formats
     */
    public function __construct(ResourceClassResolverInterface $resourceClassResolver, private readonly IriConverterInterface $iriConverter, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly SerializerInterface $serializer, private readonly array $formats, MessageBusInterface $messageBus = null, private readonly ?HubRegistry $hubRegistry = null, private readonly ?GraphQlSubscriptionManagerInterface $graphQlSubscriptionManager = null, private readonly ?GraphQlMercureSubscriptionIriGeneratorInterface $graphQlMercureSubscriptionIriGenerator = null, ExpressionLanguage $expressionLanguage = null, private bool $includeType = false)
    {
        if (null === $messageBus && null === $hubRegistry) {
            throw new InvalidArgumentException('A message bus or a hub registry must be provided.');
        }

        $this->resourceClassResolver = $resourceClassResolver;

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->messageBus = $messageBus;
        $this->expressionLanguage = $expressionLanguage ?? (class_exists(ExpressionLanguage::class) ? new ExpressionLanguage() : null);
        $this->reset();

        if ($this->expressionLanguage) {
            $rawurlencode = ExpressionFunction::fromPhp('rawurlencode', 'escape');
            $this->expressionLanguage->addFunction($rawurlencode);

            $this->expressionLanguage->addFunction(
                new ExpressionFunction('iri', static fn (string $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL): string => sprintf('iri(%s, %d)', $apiResource, $referenceType), static fn (array $arguments, $apiResource, int $referenceType = UrlGeneratorInterface::ABS_URL): string => $iriConverter->getIriFromResource($apiResource, $referenceType))
            );
        }

        if (false === $this->includeType) {
            trigger_deprecation('api-platform/core', '3.1', 'Having mercure.include_type (always include @type in Mercure updates, even delete ones) set to false in the configuration is deprecated. It will be true by default in API Platform 4.0.');
        }
    }

    /**
     * Collects created, updated and deleted objects.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if ($eventArgs instanceof OrmOnFlushEventArgs) {
            $uow = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager()->getUnitOfWork() : $eventArgs->getEntityManager()->getUnitOfWork();
        } elseif ($eventArgs instanceof MongoDbOdmOnFlushEventArgs) {
            $uow = $eventArgs->getDocumentManager()->getUnitOfWork();
        } else {
            return;
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityInsertions' : 'getScheduledDocumentInsertions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, $uow, 'createdObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityUpdates' : 'getScheduledDocumentUpdates';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, $uow, 'updatedObjects');
            //  and an update might trigger GraphQL subscriptions updates (depends on field selection and change sets)
            $this->storeObjectToPublish($object, $uow, 'subscribedToObjects');
        }

        $methodName = $eventArgs instanceof OrmOnFlushEventArgs ? 'getScheduledEntityDeletions' : 'getScheduledDocumentDeletions';
        foreach ($uow->{$methodName}() as $object) {
            $this->storeObjectToPublish($object, $uow, 'deletedObjects');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(): void
    {
        try {
            foreach ($this->createdObjects as $object) {
                $this->publishUpdate($object, $this->createdObjects[$object], 'create');
            }

            foreach ($this->updatedObjects as $object) {
                $this->publishUpdate($object, $this->updatedObjects[$object], 'update');
            }

            foreach ($this->subscribedToObjects as $pair) {
                $this->publishSubscriptionUpdate($pair, $this->subscribedToObjects[$pair], type: 'gqlsubs');
            }

            foreach ($this->deletedObjects as $object) {
                $this->publishUpdate($object, $this->deletedObjects[$object], 'delete');
            }
        } finally {
            $this->reset();
        }
    }

    private function reset(): void
    {
        $this->createdObjects = new \SplObjectStorage();
        $this->updatedObjects = new \SplObjectStorage();
        $this->deletedObjects = new \SplObjectStorage();

        $this->subscribedToObjects = new \SplObjectStorage();
    }

    private function storeObjectToPublish(object $object, UnitOfWork $uow, string $property): void
    {
        if (null === $resourceClass = $this->getResourceClass($object)) {
            return;
        }

        $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation();
        try {
            $options = $operation->getMercure() ?? false;
        } catch (OperationNotFoundException) {
            return;
        }

        if (\is_string($options)) {
            if (null === $this->expressionLanguage) {
                throw new RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $options = $this->expressionLanguage->evaluate($options, ['object' => $object]);
        }

        if (false === $options) {
            return;
        }

        if (true === $options) {
            $options = [];
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException(sprintf('The value of the "mercure" attribute of the "%s" resource class must be a boolean, an array of options or an expression returning this array, "%s" given.', $resourceClass, \gettype($options)));
        }

        foreach ($options as $key => $value) {
            if (!isset(self::ALLOWED_KEYS[$key])) {
                throw new InvalidArgumentException(sprintf('The option "%s" set in the "mercure" attribute of the "%s" resource does not exist. Existing options: "%s"', $key, $resourceClass, implode('", "', self::ALLOWED_KEYS)));
            }
        }

        $options['enable_async_update'] ??= true;

        if ($options['topics'] ?? false) {
            $topics = [];
            foreach ((array) $options['topics'] as $topic) {
                if (!\is_string($topic)) {
                    $topics[] = $topic;
                    continue;
                }

                if (!str_starts_with($topic, '@=')) {
                    $topics[] = $topic;
                    continue;
                }

                if (null === $this->expressionLanguage) {
                    throw new \LogicException('The "@=" expression syntax cannot be used without the Expression Language component. Try running "composer require symfony/expression-language".');
                }

                $topics[] = $this->expressionLanguage->evaluate(substr($topic, 2), ['object' => $object]);
            }

            $options['topics'] = $topics;
        }

        if ('deletedObjects' === $property) {
            $types = $operation instanceof HttpOperation ? $operation->getTypes() : null;
            if (null === $types) {
                $types = [$operation->getShortName()];
            }

            $this->deletedObjects[(object) [
                'id' => $this->iriConverter->getIriFromResource($object),
                'iri' => $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL),
                'type' => 1 === \count($types) ? $types[0] : $types,
            ]] = $options;

            return;
        }

        if ('subscribedToObjects' === $property) {
            //  for GraphQL subscriptions we need the object and the change sets
            $this->subscribedToObjects[(object) [
                'alpha' => $object,
                'beta' => $uow
            ]] = $options;

            return;
        }

        $this->{$property}[$object] = $options;
    }

    private function publishUpdate(object $object, array $options, string $type): void
    {
        if ($object instanceof \stdClass) {
            // By convention, if the object has been deleted, we send only its IRI and its type.
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach.
            $iri = $options['topics'] ?? $object->iri;
            /** @var string $data */
            $data = json_encode(['@id' => $object->id] + ($this->includeType ? ['@type' => $object->type] : []), \JSON_THROW_ON_ERROR);
        } else {
            $resourceClass = $this->getObjectClass($object);
            $context = $options['normalization_context'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation()->getNormalizationContext() ?? [];

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL);
            $data = $options['data'] ?? $this->serializer->serialize($object, key($this->formats), $context);
        }

        $updates = [$this->buildUpdate($iri, $data, $options, $type)];

        foreach ($updates as $update) {
            if ($options['enable_async_update'] && $this->messageBus) {
                $this->dispatch($update);
                continue;
            }

            $this->hubRegistry->getHub($options['hub'] ?? null)->publish($update);
        }
    }

    private function publishSubscriptionUpdate(object $pair, array $options, string $type): void
    {
        $object = $pair->alpha;
        $uow = $pair->beta;

        if ($object instanceof \stdClass) {
            // By convention, if the object has been deleted, we send only its IRI and its type.
            // This may change in the feature, because it's not JSON Merge Patch compliant,
            // and I'm not a fond of this approach.
            $iri = $options['topics'] ?? $object->iri;
            /** @var string $data */
            $data = json_encode(['@id' => $object->id] + ($this->includeType ? ['@type' => $object->type] : []), \JSON_THROW_ON_ERROR);
        } else {
            $resourceClass = $this->getObjectClass($object);
            $context = $options['normalization_context'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation()->getNormalizationContext() ?? [];

            $iri = $options['topics'] ?? $this->iriConverter->getIriFromResource($object, UrlGeneratorInterface::ABS_URL);
            $data = $options['data'] ?? $this->serializer->serialize($object, key($this->formats), $context);
        }

        $updates = $this->getGraphQlSubscriptionUpdates($object, $uow, $options, $type);

        foreach ($updates as $update) {
            if ($options['enable_async_update'] && $this->messageBus) {
                $this->dispatch($update);
                continue;
            }

            $this->hubRegistry->getHub($options['hub'] ?? null)->publish($update);
        }
    }

    /**
     * @return Update[]
     */
    private function getGraphQlSubscriptionUpdates(object $object, UnitOfWork $uow, array $options, string $type): array
    {
        if ('gqlsubs' !== $type || !$this->graphQlSubscriptionManager || !$this->graphQlMercureSubscriptionIriGenerator) {
            return [];
        }

        //  available subscriptions and their selection fields
        $payloads = $this->graphQlSubscriptionManager->getPushPayloads($object);

        //  TODO check Doctrine docs, lots of 'i guess this is...' at work here
        //  ORM changed fields
        $changeSets = $uow->getEntityChangeSet($object);

        $updates = [];
        foreach ($payloads as [$subscriptionId, $data]) {
            //  GraphQL subscribed fields
            //  TODO check $data construction and what we might expect here
            //  don't know if $data might contain more than one key
            assert(count(array_keys($data)) <= 1);
            $fieldSelection = array_reduce($data, 'array_merge', array());

            //  only publish update for GraphQL subscription if fields we're interested in actually changed
            $match = array_intersect_key($fieldSelection, $changeSets);
            if (count($match) > 0) {
                $updates[] = $this->buildGraphQlSubscriptionUpdate(
                    $this->graphQlMercureSubscriptionIriGenerator->generateTopicIri($subscriptionId),
                    (string)(new JsonResponse($data))->getContent(),
                    $options, 'gqlsubs'
                );
            }
        }

        return $updates;
    }

    /**
     * @param string|string[] $iri
     */
    private function buildUpdate(string|array $iri, string $data, array $options, string $type): Update
    {
        return new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $type, $options['retry'] ?? null);
    }

    /**
     * @param string|string[] $iri
     */
    private function buildGraphQlSubscriptionUpdate(string|array $iri, string $data, array $options, string $type): Update
    {
        //  this is a GraphQL Subscription update, tell Mercure that it is so it simplifies the data sent (skip topics)
        return new Update($iri, $data, $options['private'] ?? false, $options['id'] ?? null, $type, $options['retry'] ?? null);
    }
}
