# Async Curl

This package will help you to send any request to any server in an asynchronous way!
Just follow the instructions in order to install and setup the async curl package.

```bash
> composer require hasanparasteh/async-curl
```

## Quickstart Example

This is the simplest way to do a `GET` request. The results will be in a callable function which has 3 major data in it.

1. result: `bool`=> represent that curl is successful or not
2. code: `int`=> http status code
3. body: `array`=> json decoded array which server returned
4. error: `string`=> description of the curl error

```php
$request = new AsyncCurl("https://reqres.in");
$request->get("/api/users", ["page" => 2])->then(function ($result) {
    if (!$result['result'])
        echo "Curl Error cause {$result['error']}";
    else
        switch ($result['code']) {
            case 200:
                echo "Server Response 200 With " . json_encode($result['body'], 128);
                break;
            case 400:
                echo "Server Response 400";
                break;
            case 500:
                echo "Server Response 500";
                break;
            // .. and any other response Code
        }
});
```

### GET

if you need to pass any query params just sends the as an array to the second argument and if you need to add any header
just pass it in the third argument as an array.

```php
$request->get("endpoint")
```

### POST

It's just like the `GET` request but it sends the paramethers as a json encoded raw!

```php
$request->get("endpoint", ['paramName' => 'paramValue' ], ['headerName'=>'headerValue']);
```

### PUT

It's exactly like the `POST`.

```php
$request->put("endpoint")
```

### PATCH

It's exactly like the `POST`.

```php
$request->patch("endpoint")
```

### DELETE

It's exactly like the `POST`.

```php
$request->delete("endpoint")
```


