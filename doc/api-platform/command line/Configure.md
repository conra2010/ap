
# Environment
All commands use the _fish_ shell syntax; probably some minor changes for _bash_.

The Mercure server is configured with an ad-hoc certification authority, and some commands need the certificates of both the _root_ and the _intermediate_ authorities to be able to verify the server.
```shell (fish)
set CA_BUNDLE (pwd)/api/docker/ca/ca-bundle.crt
set AP_ENTRYPOINT https://caddy.api-platform.orb.local
set MERCURE_ENTRYPOINT https://caddy.api-platform.orb.local
```
The JWT token can be copied from the Mercure Debug UI that should be running [in your API Platform Caddy](https://caddy.api-platform.orb.local/.well-known/mercure/ui/#discover)
```shell
open $AP_ENTRYPOINT/.well-known/mercure/ui/#discover
```
Copy the value of the JWT token, and save it into another variable:
```shell (fish)
set JWT_TOKEN (pbpaste)
```
# Tools
I'll use [httpie cli client](https://httpie.io/cli) as HTTP client, and [jq](https://jqlang.github.io/jq/) for JSON processing. 