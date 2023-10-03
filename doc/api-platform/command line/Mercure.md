# Notes
All commands use the _fish_ shell syntax.
The Mercure server is configured with an ad-hoc certification authority, so _httpie_ needs the certificates of both the _root_ and the _intermediate_ authorities to be able to verify the server.
# Watch subscriptions
```shell (fish)
while true; clear; date; \
	http --verify $CA_BUNDLE --pretty none -b \
		GET https://shodan.local:8443/.well-known/mercure/subscriptions \
		"Authorization: Bearer $JWT_TOKEN"|jq '.subscriptions'; \
	sleep 3; \
end
```
# Send updates
Send sequence of fake/debug updates to a topic. 
```shell
seq 1000 | while read line; \
    set TS (date); \
    set DTA '{"@context":"/contexts/Post","@id":"/post/503","@type":"Post","id":493,"title":"Post Title","content":"Post Content Text","httpie":"'$line'","ts":"'$TS'"}'; \
    set RESPONSE (http --verify $CA_BUNDLE -b --ignore-stdin -f POST https://shodan.local:8443/.well-known/mercure topic='https://caddy.ap.orb.local/posts/493' data=$DTA "Authorization:Bearer $JWT_TOKEN" type='message'); \
    echo $line: '{"rq":'$DTA',"rx":{"id":"'$RESPONSE'"}}'; \
    sleep 5; \
end
```
