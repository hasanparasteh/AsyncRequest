<?php

require "vendor/autoload.php";

use hasanparasteh\AsyncCurl;


$request = new AsyncCurl("https://httpstat.us", "72.221.172.203:4145");
$request->get("/200", ["sleep" => 66000])->then(function ($result) {
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