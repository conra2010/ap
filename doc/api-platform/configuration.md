# docker
Given the _.env_ file with a _COMPOSE_PROJECT_NAME_ variable and running on OrbStack,  the Caddy service will be at server _caddy.api-platform.orb.local_.
Let's setup HTTPS with our own authority.
*TODO follow Step CLI documentation/quick start to generate a certificate and key for the server*
Create a _ca_store_ volume and copy certs there; will mount this when needed.
```
cp $CA_STORE/caddy.api-platform.orb.local.crt .
cp $CA_STORE/caddy.api-platform.orb.local.key .
# decrypt key for server
openssl ec -in caddy.api-platform.orb.local.key -out caddy.api-platform.orb.local.key~
mv caddy.api-platform.orb.local.key~ caddy.api-platform.orb.local.key
```
*TODO Caddyfile mods for this*
Startup the platform:
```
docker compose build --no-cache
docker compose up --pull --wait
```
Should be able to visit [the web ui](https://caddy.api-platform.orb.local) 
# GraphQL
Enable according to docs:
```
docker compose exec php sh -c ' composer require webonyx/graphql-php bin/console cache:clear'
```
Should be able to visit [the GraphQL Playground](https://caddy.api-platform.orb.local/graphql/graphql_playground) and execute a simple query:
```
{
  greetings {
    totalCount
  }
}
```
# Sample Resources
Following the docs ("Testing the API") I've setup a mock resource _Post_ with some totally made up attributes.
```
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate
```
Should be able to see the resource in the GraphQL Schema; refresh the playground app and check the "DOCS" tab.
Let's create fixtures for testing; see the code for the factory, story and fixtures.
```
docker compose exec php composer require --dev foundry orm-fixtures
```
```
docker compose exec php bin/console make:factory 'App\Entity\Post'
```
```
docker compose exec php bin/console make:story 'DefaultPosts'
```
Should be able to list the IDs of posts in GraphQL:
```
{
  posts {
    id
  }
}
```
# CORS
Allow access to the "/graphql" path from the web app in the API Platform configuration. Allow access to Mercure in the Caddyfile from the web app.
# Vue Sample
Create project:
```
pnpm create vue@latest
api-platform-vue
(typescript)
(no jsx support)
(vue router)
(pinia state management)
(no vitest)
(no testing solution)
(no eslint)
cd api-platform-vue
pnpm install
```
Setup GraphQL support:
```
pnpm add --save @urql/vue graphql
pnpm add @urql/core
```
Setup Quasar:
```
pnpm add quasar @quasar/extras
pnpm add -D @quasar/vite-plugin sass@1.32.12
```
Other packages:
```
pnpm add @vueuse/core
pnpm add uuid
```
# API Platform Issues
Will override in _composer.json_ to change _vendor_ code.
```
docker compose exec php composer dump-autoload
```
## GraphQL
### Subscription URN (001)
Only takes selection fields into account, not the actual resource ID. All "Post" resources will get updates about field changes, even when the modified "Post" is another resource.
### Subscription Selection Fields (003)
With a GraphQL subscription on fields "author, title and stars", we don't want updates about mutations on other fields. Issue a mutation on "version" in the Playground and the subscription still gets an event about "author, title and stars".
## SSE
### Message type is not sent (002)
The _$type_ is there, but it's not finally sent to the hub.
