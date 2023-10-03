# API Platform Changes
Will override in _composer.json_ to change _vendor_ code.
## GraphQL
### Subscription URN (001)
Only takes selection fields into account, not the actual resource ID. All "Post" resources will get updates about field changes, even when the modified "Post" is another resource.
```shell
cdx api/overrides/api-platform/core/src/ApiPlatform
```
### Subscription Selection Fields (003)
With a GraphQL subscription on fields "author, title and stars", we don't want updates about mutations on other fields. Issue a mutation on "version" in the Playground and the subscription still gets an event about "author, title and stars".
## SSE
### Message type is not sent (002)
The _$type_ is there, but it's not finally sent to the hub.
