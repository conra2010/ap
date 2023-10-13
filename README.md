> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

This example will start a modified API Platform in Docker Containers. The caddy service should be available at
```
open https://{$SERVER_NAME}
```
## Setup
Review some configuration values in the project.
```shell
cat .env
```

```shell
docker compose build --no-cache
docker compose up --pull --wait
```
## Testing GraphQL
Open the welcome page and the GraphQL Playground page.
```
open https://{$SERVER_NAME}/
open https://{$SERVER_NAME}/graphql/graphql_playground
```
Let's try some GraphQL queries first. There's a set of fixtures defined in the platform; load them up:
```shell
docker compose exec php bin/console doctrine:fixtures:load
```
And query the "Post(s)" in the playground:
```graphql
{
	posts { id }
}
```
Notice that _pagination_ is disabled for this resource, so we'll get a list of IDs.