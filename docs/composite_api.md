[Back](index.md)

# Composite API
Allows API consumers to sideload/chain load requests to the API.

### Async sideloads
Asynchronously load multiple requests to the API.

POST https://your.api.com/1.0/sideloads
```json
{
    "/1.0/systems/search": {
        "method": "get"
    },
    "/1.0/systems/search?filters[id]=1": {
        "method": "get"
    }
}
```

### Synchronous Chain Requests
Load a chain of requests and pass through data from responses to future requests.

POST https://your.api.com/1.0/sideloads/chain
```json
{
	"/1.0/systems/search": {
		"method": "get",
		"ref": "systems_search"
	},
	"/1.0/systems/search?filters[id]==%{systems_search.data.id}": {
		"method": "get",
		"ref": "filtered",
		"body": {
		    "foo": "%{systems_search.data.label}"
		}
	}
}
```

### Setup
1. Configure your routes:
    ```php
    Route::post('sideloads', '\Fuzz\ApiServer\CompositeAPI\CompositeAPIController@parallel');
    Route::post('sideloads/chain', '\Fuzz\ApiServer\CompositeAPI\CompositeAPIController@chain');
    ```
1. Set up the `APP_URL` environment variable