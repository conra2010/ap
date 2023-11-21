> API Platform is a next-generation web framework designed to easily create API-first projects without compromising extensibility and flexibility.
> 
> The official project documentation is available **[on the API Platform website](https://api-platform.com)**.

This example will use a modified API Platform, and is based on the official distribution. In the Docker definition the web server is Caddy and the corresponding _caddy_ service will be available at
```bash
open https://{$SERVER_NAME}
```
once we decide a server name and start the containers. The server name depends on how you access the service.
```shell
# some (use yours) Tailscale network
set SERVER_NAME loom.elf-basilisk.ts.net
```
```shell
# or maybe using OrbStack container name
set SERVER_NAME caddy.api-platform.orb.local
```
All _shell_ commands use the [fish shell](https://fishshell.com/).
```shell
set git clone ...
set AP_ROOT (pwd)/ap
cd {$AP_ROOT}
```

Since we are using HTTPS, we will need a certificate/key pair for the web server; certificates are created for specific host and domain names, so the ones provided in _api/docker/ca_ are valid for those names. If you need to use a different server name, see [doc/api-platform/A002 Configuration](doc/api-platform/A002_Configuration.md) to setup a certification authority and issue certificates.

Right now in my setup I'm testing Tailscale and I'm using a server name _ukemochi.elf-basilisk.ts.net_ to be able to access the platform in my VPN.

Before this I've also used the server names that _OrbStack_ provides for Docker services. Did some testing with WireGuard VPN ([linuxserver.io image](https://github.com/linuxserver/docker-wireguard)) and Pi-Hole for DNS, but the Tailscale setup is much easier.
## Setup
Review some configuration values in the project.
```bash
cp .env .env.local
cat .env.local
```
See [doc/api-platform/A002 Configuration](doc/api-platform/A002_Configuration.md) if you need to change the server name, to issue certificates for it.

Review the _api/docker/caddy/Caddyfile_ and the certificate/key pair in the _ca_ folder; check the server name and the _tls_ directive that points to the certificate/key for it. You'll also need to change the _cors_origins_ directive to the name of the development web server if you want to check out my _Vue_ example app (_blog-vue_ repository).

Create a network for the containers:
```shell
docker network create --driver bridge api-platform
```

Build the images:
```shell
docker compose --env-file .env.local build --no-cache --progress plain
```

And start them up:
```shell
docker compose --env-file .env.local up --pull --wait
```

Check the logs of the _caddy_ service, it usually spits out info about configuration errors on startup. 

If you are using Tailscale, open a privileged shell into the _caddy_ service to install Tailscale into it.
```
docker compose exec --privileged caddy sh
apk add tailscale
tailscaled --tun=userspace-networking --socks5-server=localhost:1055 &
tailscale up
```
Login in the web browser and change the name of the machine in the Tailscale admin web to the hostname in ${SERVER_NAME}.

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
# Other docs
In the _doc/api-platform_ folder you will find some documents about the changes I've made to the API Platform, and some notes/reminders on how to query both the Mercure Hub and the GraphQL API from the command line.

In my other repository _blog-vue_ you can check out a Vue.js sample app that uses GraphQL Queries, Mutations and Subscriptions.