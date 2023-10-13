> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

This example will start a modified API Platform in Docker Containers, and expects the Caddy service to be added to a Tailscale VPN.
## Setup
Review some configuration values in the project.
```shell
cat .env
```

```shell
docker compose build --no-cache
docker compose up --pull --wait
```
Open a privileged shell into the _caddy_ service to install Tailscale into it.
```
docker compose exec --privileged caddy sh
apk add tailscale
tailscaled --tun=userspace-networking --socks5-server=localhost:1055 &
tailscale up
```
Login in the web browser and change the name of the machine in the Tailscale admin web to the ${SERVER_NAME}.
## Testing GraphQL
Open the [welcome page of the platform](http://benzaiten/) and the [GraphQL Playground app](http://benzaiten/graphql/graphql_playground).
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