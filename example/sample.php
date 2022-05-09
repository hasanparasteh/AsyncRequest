<?php

require "vendor/autoload.php";

use hasanparasteh\AsyncRequest;


$request = new AsyncRequest("https://example.com");
$request->get("/test")->then(function ($result) {
    echo json_encode($result);
    if (!$result['result'])
        echo "Curl Error cause {$result['error']}";
    else
        echo match ($result['code']) {
            200 => "Server Response 200 With " . json_encode($result['body'], 128),
            400 => "Server Response 400",
            500 => "Server Response 500",
            404 => "Server Response 404",
            default => "Unexpected!",
        };
});