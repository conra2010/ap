# docker
## setup
Given the _.env_ file with a _COMPOSE_PROJECT_NAME_ variable, the Caddy service will be running at server name _caddy.api-platform.orb.local_. Let's setup HTTPS with our own authority.
*TODO follow Step CLI documentation/quick start to generate a certificate and key for the server
d
Create a _ca_store_ volume and copy certs there; will mount this when needed.
```
cp $CA_STORE/caddy.api-platform.orb.local.crt .
cp $CA_STORE/caddy.api-platform.orb.local.key .
openssl ec -in caddy.api-platform.orb.local.key -out caddy.api-platform.orb.local.key~
mv caddy.api-platform.orb.local.key~ caddy.api-platform.orb.local.key
```
*TODO Caddyfile mods for this*



