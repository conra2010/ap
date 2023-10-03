# API Platform Changes
Will override in _composer.json_ to change _vendor_ code.
## GraphQL
### Subscription URN (001)
Commit: 7ba3293f0be21cf1ceedaff914e6492d88508a98
The original API Platform 3.1.14 uses only the selection fields of the subscription to compute a subscription ID, even though in the GraphQL operation we've given (as directed by the documentation) the identifier of the particular resource we are interested in.
When executing a subscription for fields "author, title" on the Post with ID "/posts/54", I'd expect to be notified of changes on those fields and that particular Post.
#### Changes
Just include the resource ID in the computation of the subscription ID, together with the field selection.
### Subscription Selection Fields (003)
Commit: 4a6b8cdfeea4b0c96276989054aca5d04153b4ef
With a GraphQL subscription on fields "author, title and stars", we don't want updates about mutations on other fields. Issue a mutation on "version" in the Playground and the subscription still gets an event about "author, title and stars".
#### Changes
The API Platform code keeps three stores for created, updated and deleted objects. It tries to use the same logic for updates and GraphQL subscriptions.
I have defined a new store just for GraphQL subscriptions, a "gqlsubs" event type to go with it, and use the Doctrine UnitOfWork to actually publish events only to subscribers for the changed fields.
## SSE
### Message type is not sent (002)
Commit: 356ebf6b6836b44380d8da6d18a563d402c085aa
The _$type_ is there, but it's not finally sent to the hub. It is interesting to have that information in the event, and know if it is an insertion, update or deletion (for example). I'll later include a new type for GraphQL subscriptions.
#### Changes
Actually pass the $type when creating updates.
