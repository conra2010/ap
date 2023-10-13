# Notes
You can use "developer tools" in a web browser to see the request that the GraphQL Playground web app sends to your server.
# Simple query
Construct query body; in this particular case _pagination_ is disabled in the server.
```shell
set QUERY (jq -c -n --arg query '{ posts { id } }' '{"query":$query}')
```
Complete JSON and send request:
```shell
echo $QUERY|jq '. + {operationName:null,variables:{}}'|http POST http://{$SERVER_NAME}/graphql
```
# Mutation with variables
## Insert a new resource
## Delete a resource
## Update a resource
Construct mutation body:
```shell
set QUERY (jq -c -n --arg query '
               mutation UPD ($id:ID!,$title:String!) {
               updatePost (input:{id:$id,clientMutationId:"playground",title: $title}) {
               clientMutationId
               }
               }
               ' '{"query":$query}')
```

Use vars to execute the mutation:
```shell
echo $QUERY|jq --arg id "/posts/1" --arg title "Hello!" '. + {operationName:"UPD",variables: {id:$id,title:$title}}'|http POST http://{$SERVER_NAME}/graphql
```

And check the change in the playground:
```graphql
{
  post(id:"/posts/1") {
    title
  }
}
```
