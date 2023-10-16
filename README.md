> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

This example will start a modified API Platform in Docker Containers. The caddy service should be available at
```
open https://{$SERVER_NAME}
```
Since we are using HTTPS, the certificates in _api/docker/ca_ are valid for specific server names. Right now I'm testing Tailscale and the server name is _ukemochi_.
## Setup
Review some configuration values in the project.
```shell
cp .env .env.local
cat .env.local
```
See _doc/api-platform/A002 Configuration_ if you need to change the server name, to setup certificates for it.

```shell
docker compose --env-file .env.local build --no-cache
docker compose --env-file .env.local up --pull --wait
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