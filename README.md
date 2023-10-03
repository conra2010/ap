> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

## Setup
```shell
docker compose build --no-cache
docker compose up --pull --wait
```
This should start the Caddy web server that hosts the platform; the Docker service name is _caddy_ so when working with OrbStack, it'll be running at _caddy.api-platform.orb.local:443_.
You can install the provided certification authority
```shell
api/docker/ca/root_ca.crt
api/docker/ca/intermediate_ca.crt
```
for your browser to trust the server certificate.
If you're using Docker Desktop you'll probably have to forward some ports in the docker-compose.yml file or use some kind of proxy.
## Testing GraphQL
Open the [welcome page of the platform](https://caddy.api-platform.orb.local/) and the [GraphQL Playground app](https://caddy.api-platform.orb.local/graphql/graphql_playground).
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