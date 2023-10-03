# Watch subscriptions
Let's get the current subscriptions in Mercure:
```shell (fish)
http --verify $CA_BUNDLE --pretty none -b \
	GET $MERCURE_ENTRYPOINT/.well-known/mercure/subscriptions \
	"Authorization: Bearer $JWT_TOKEN"
```
Now let's just watch the subscribers and the topic they are subscribed to:
```shell (fish)
while true; clear; date; \
	http --verify $CA_BUNDLE --pretty none -b \
		GET $MERCURE_ENTRYPOINT/.well-known/mercure/subscriptions \
		"Authorization: Bearer $JWT_TOKEN"|jq '.subscriptions[]|(.subscriber,.topic)'; \
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
