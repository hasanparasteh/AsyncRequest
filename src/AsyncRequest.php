<?php

namespace hasanparasteh;

use Clue\React\Socks\Client;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\PromiseInterface;
use React\Socket\Connector;

use function React\Promise\Timer\timeout;

class AsyncRequest
{
    protected string $baseUrl;
    protected ?string $proxyUrl;
    protected Browser $browser;
    protected float $timeout;
    protected bool $isLoggingEnabled = false;

    protected array $headers = [];

    public function __construct(string $baseUrl, string $proxyUrl = null, float $timeout = 5.0, bool $bypass_ssl = false, bool|int $followRedirects = false)
    {
        $this->baseUrl = $baseUrl;
        $this->proxyUrl = $proxyUrl;
        $this->timeout = $timeout;

        $connectorOptions = [];
        $connectorOptions['timeout'] = $timeout;

        if (!is_null($proxyUrl)) {
            $proxy = new Client($this->proxyUrl, new Connector());
            $connectorOptions['tcp'] = $proxy;
            $connectorOptions['dns'] = false;
        }

        if ($bypass_ssl)
            $connectorOptions['tls'] = [
                'verify_peer' => false,
                'verify_peer_name' => false
            ];


        $this->browser = (new Browser(new Connector($connectorOptions)))
            ->withTimeout($timeout)
            ->withFollowRedirects($followRedirects);
    }

    public function enableLogging(): void
    {
        $this->isLoggingEnabled = true;
    }

    public function disabledLogging(): void
    {
        $this->isLoggingEnabled = false;
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function get(string $url, array $params = [], array $headers = [], string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'GET', $contentType, $canResponseDecode);
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function post(string $url, array $params = [], array $headers = [], string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'POST', $contentType, $canResponseDecode);
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function put(string $url, array $params = [], array $headers = [], string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'PUT', $contentType, $canResponseDecode);
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function patch(string $url, array $params = [], array $headers = [], string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'PATCH', $contentType, $canResponseDecode);
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function delete(string $url, array $params = [], array $headers = [], string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'DELETE', $contentType, $canResponseDecode);
    }

    private function request(string $url, array $params = [], array $headers = [], string $type = 'GET', string $contentType = 'application/json', bool $canResponseDecode = true): PromiseInterface
    {
        $url = $this->baseUrl . $url;
        $headers['Content-Type'] = $contentType;

        if (count($this->headers) > 0)
            $headers = array_merge($headers, $this->headers);

        if ($type == 'GET' && count($params) > 0)
            $url = $url . "?" . http_build_query($params);

        if (empty($params) || count($params) == 0)
            $params = "";
        else if (str_contains($contentType, "form")) {
            $params = http_build_query($params);
            $canResponseDecode = false;
        } else
            $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($this->isLoggingEnabled) {
            echo '------------------ REQUEST ------------------' . PHP_EOL;
            echo "Requesting $url and METHOD is: " . $type . PHP_EOL;
            echo "Body is: " . PHP_EOL . json_encode($params, 128) . PHP_EOL;
            echo "Header is: " . PHP_EOL . json_encode($headers, 128) . PHP_EOL;
            echo '------------------ END OF REQUEST ------------------' . PHP_EOL;
        }

        if ($type != 'GET')
            $req = $this->browser->request($type, $url, $headers, $params);
        else
            $req = $this->browser->request($type, $url, $headers);


        // Added Request Timeout
        return timeout($req, $this->timeout)->then(
            function ($response) use ($canResponseDecode) {
                if ($response instanceof ResponseInterface) {
                    if (str_contains($response->getHeaderLine('Content-Type'), "image/") ||
                        str_contains($response->getHeaderLine('Content-Type'), "video/")
                    ) {

                        if ($this->isLoggingEnabled) {
                            echo '------------------ RESPONSE ------------------' . PHP_EOL;
                            echo 'Status Code: ' . $response->getStatusCode() . PHP_EOL;
                            echo 'Headers is: ' . json_encode($response->getHeaders(), 128) . PHP_EOL;
                            echo 'Body is: ' . $response->getBody()->__toString() . PHP_EOL;
                            echo '------------------ END OF RESPONSE ------------------' . PHP_EOL;
                        }

                        return [
                            'result' => true,
                            'code' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => $response->getBody()->__toString()
                        ];
                    } else {

                        if ($this->isLoggingEnabled) {
                            echo '------------------ RESPONSE ------------------' . PHP_EOL;
                            echo 'Status Code: ' . $response->getStatusCode() . PHP_EOL;
                            echo 'Headers is: ' . json_encode($response->getHeaders(), 128) . PHP_EOL;
                            echo 'Body is: ' . $canResponseDecode
                                ? json_decode($response->getBody()->getContents(), true)
                                : $response->getBody()->getContents() . PHP_EOL;
                            echo '------------------ END OF RESPONSE ------------------' . PHP_EOL;
                        }

                        return [
                            'result' => true,
                            'code' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => $canResponseDecode
                                ? json_decode($response->getBody()->getContents(), true)
                                : $response->getBody()->getContents()
                        ];
                    }

                }

                if ($response instanceof ResponseException) {
                    if ($this->isLoggingEnabled) {
                        echo '------------------ RESPONSE EXCEPTION ------------------' . PHP_EOL;
                        echo 'Status Code: ' . $response->getCode() . PHP_EOL;
                        echo 'Error: ' . $response->getTraceAsString() . PHP_EOL;
                        echo '------------------ END OF RESPONSE EXCEPTION ------------------' . PHP_EOL;

                    }

                    return [
                        'result' => false,
                        'code' => $response->getCode(),
                        'body' => $response->getTraceAsString()
                    ];
                }

                return [
                    'result' => false,
                    'code' => 100_000,
                ];
            },
            function ($error) use ($canResponseDecode) {
                if ($error instanceof ResponseException) {
                    $response = $error->getResponse();
                    if ($this->isLoggingEnabled) {
                        echo '------------------ RESPONSE ------------------' . PHP_EOL;
                        echo 'Status Code: ' . $response->getStatusCode() . PHP_EOL;
                        echo 'Headers is: ' . json_encode($response->getHeaders(), 128) . PHP_EOL;
                        echo 'Body is: ' . $canResponseDecode
                            ? json_decode($response->getBody()->getContents(), true)
                            : $response->getBody()->getContents() . PHP_EOL;
                        echo '------------------ END OF RESPONSE ------------------' . PHP_EOL;
                    }

                    return [
                        'result' => true,
                        'code' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => $canResponseDecode
                            ? json_decode($response->getBody()->getContents(), true)
                            : $response->getBody()->getContents()
                    ];
                }

                return [
                    'result' => false,
                    'error' => $error->getMessage()
                ];
            }
        );
    }

    public function addHeaders(array $headers): AsyncRequest
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
}
