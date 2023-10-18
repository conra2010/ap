# Environment
All commands use the _fish_ shell syntax; probably some minor changes for _bash_.
```shell (fish)
set CA_BUNDLE (pwd)/api/docker/ca/ca-bundle.crt
alias httpx 'http --verify {$CA_BUNDLE}'
set AP_ENTRYPOINT https://{$SERVER_NAME}
set MERCURE_ENTRYPOINT https://{$SERVER_NAME}
set MERCURE_TOPICS_PREFIX https://{$SERVER_NAME}
```
The JWT token can be copied from the Mercure Debug UI that should be running here:
```shell
open $MERCURE_ENTRYPOINT/.well-known/mercure/ui/#discover
```
Paste the following command, then copy the value of the JWT token, and then execute the command to save the token into another variable:
```shell (fish)
set JWT_TOKEN (pbpaste)
```
# Tools
I'll use [httpie cli client](https://httpie.io/cli) as HTTP client, and [jq](https://jqlang.github.io/jq/) for JSON processing. 