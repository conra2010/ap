# Certification Authority
Setup the command line _Step_ tool:
```
brew install step
```
And follow the ...
# WireGuard
# Test: Tailscale
Open a privileged shell into the _caddy_ service to install Tailscale into it.
```
docker compose exec --privileged caddy sh
apk add tailscale
tailscaled --tun=userspace-networking --socks5-server=localhost:1055 &
tailscale up
```
Login in the web browser and change the name of the machine in the Tailscale admin web to the ${SERVER_NAME}.