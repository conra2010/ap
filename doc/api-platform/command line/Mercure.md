# Watch subscriptions
Let's get the current subscriptions in Mercure:
```shell (fish)
http --pretty none -b \
	GET $MERCURE_ENTRYPOINT/.well-known/mercure/subscriptions \
	"Authorization: Bearer $JWT_TOKEN"
```
Now let's just watch the subscribers and the topic they are subscribed to:
```shell (fish)
while true; clear; date; \
	http --pretty none -b \
		GET $MERCURE_ENTRYPOINT/.well-known/mercure/subscriptions \
		"Authorization: Bearer $JWT_TOKEN"|jq '.subscriptions[]|(.subscriber,.topic)'; \
	sleep 3; \
end
```
# Subscribe to topics
Listen to events about the "Post" resource; the modified API Platform will publish the type of change (insert, update, delete, GraphQL Subscription) in the _event_ field of the stream:
```shell (fish)
http -p hbm --stream GET {$MERCURE_ENTRYPOINT}'/.well-known/mercure?topic='{$MERCURE_TOPICS_PREFIX}'/posts/{id}'
```
Insert, update or delete some Post resources (in the GraphQL Playground, or in the Vue sample app, for instance) to receive some events.
```graphql
mutation Upd {
  updatePost (input:{
    id:"/posts/1",
    clientMutationId:"pg",
  	title:"Hello Again!"}) {
    
    clientMutationId
  }
}
```
# Send updates
Send sequence of fake/debug updates to a topic. 
```shell
seq 1000 | while read line; \
    set TS (date); \
    set DTA '{"@context":"/contexts/Post","@id":"/post/1","@type":"Post","id":1,"title":"Post Title","content":"Post Content Text","httpie":"'$line'","ts":"'$TS'"}'; \
    set RESPONSE (http -b --ignore-stdin -f POST {$MERCURE_ENTRYPOINT}/.well-known/mercure topic={$MERCURE_TOPICS_PREFIX}'/posts/1' data=$DTA "Authorization:Bearer $JWT_TOKEN" type='message'); \
    echo $line: '{"rq":'$DTA',"rx":{"id":"'$RESPONSE'"}}'; \
    sleep 5; \
end
```
