> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

This example will use a modified API Platform, and is based on the official distribution. In the Docker definition the web server is Caddy and the corresponding _caddy_ service will be available at
```
open https://{$SERVER_NAME}
```
once we decide a server name and start the containers.

Since we are using HTTPS, we will need a certificate/key pair for the web server; the certificates are created for specific host and domain names, so the ones provided in _api/docker/ca_ are valid for those names. If you need to use a different server name, see _doc/api-platform/A002 Configuration_  to setup a certification authority and issue certificates.

Right now I'm testing Tailscale and I'm using a server name _ukemochi.elf-basilisk.ts.net_ to be able to access the platform in the VPN.

Before this I've also used the server names that _OrbStack_ provides for Docker services.
## Setup
Review some configuration values in the project.
```shell
cp .env .env.local
cat .env.local
```
See _doc/api-platform/A002 Configuration_ if you need to change the server name, to setup certificates for it.

Review the _Caddyfile_ and the certificate/key pair in the _ca_ folder; check the server name and the _tls_ directive that points to the certificate/key for it. You'll need to change the _cors_origins_ directive to the name of the development web server if you want to check out the _Vue_ example app.

The API Platform docs recommend building and starting the images using:
```shell
docker compose --env-file .env.local build --no-cache
docker compose --env-file .env.local up --pull --wait
```

Check the logs of the _caddy_ service, it usually spits out info about configuration errors on startup. 

> note: ubuntu guest (vmware fusion host) running docker is missing the _gateway_ in the default bridge, the build will fail (won't be able to connect to alpine mirror); needs _daemon.json_ with option:
```yaml
{
	"default-gateway": "172.17.0.1"
}
```
## Testing GraphQL
Once the images are up, open the welcome page and the GraphQL Playground page.
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