# Environment
All commands use the _fish_ shell syntax; probably some minor changes for _bash_.
```shell (fish)
set AP_ENTRYPOINT http://{$SERVER_NAME}
set MERCURE_ENTRYPOINT http://{$SERVER_NAME}
set MERCURE_TOPICS_PREFIX http://{$SERVER_NAME}
```
The JWT token can be copied from the Mercure Debug UI that should be running [in your API Platform Caddy](https://caddy.api-platform.orb.local/.well-known/mercure/ui/#discover)
```shell
open $MERCURE_ENTRYPOINT/.well-known/mercure/ui/#discover
```
Paste this command, then copy the value of the JWT token, and then execute the command to save the token into another variable:
```shell (fish)
set JWT_TOKEN (pbpaste)
```
# Tools
I'll use [httpie cli client](https://httpie.io/cli) as HTTP client, and [jq](https://jqlang.github.io/jq/) for JSON processing. 