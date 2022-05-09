<?php

namespace hasanparasteh;

use Clue\React\Socks\Client;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\PromiseInterface;
use React\Promise\Timer\TimeoutException;
use React\Socket\Connector;

use function React\Promise\Timer\timeout;

class AsyncRequest
{
    protected string $baseUrl;
    protected ?string $proxyUrl;
    protected Browser $browser;
    protected float $timeout;

    public function __construct(string $baseUrl, string $proxyUrl = null, float $timeout = 5.0)
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

        $this->browser = new Browser(new Connector($connectorOptions));
        $this->browser->withRejectErrorResponse(true);
        $this->browser->withTimeout($timeout);
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function get(string $url, array $params = [], array $headers = [], string $contentType = 'application/json'): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'GET', $contentType);
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function post(string $url, array $params = [], array $headers = [], string $contentType = 'application/json'): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'POST', $contentType);
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function put(string $url, array $params = [], array $headers = [], string $contentType = 'application/json'): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'PUT', $contentType);
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function patch(string $url, array $params = [], array $headers = [], string $contentType = 'application/json'): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'PATCH', $contentType);
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param string $contentType
     * @return PromiseInterface
     */
    public function delete(string $url, array $params = [], array $headers = [], string $contentType = 'application/json'): PromiseInterface
    {
        return $this->request($url, $params, $headers, 'DELETE', $contentType);
    }

    private function request(string $url, array $params = [], array $headers = [], string $type = 'GET', string $contentType = 'application/json'): PromiseInterface
    {
        $url = $this->baseUrl . $url;
        $headers['Content-Type'] = $contentType;

        if ($type == 'GET' && count($params) > 0)
            $url = $url . "?" . http_build_query($params);

        if (empty($params) || count($params) == 0)
            $params = "";
        else
            $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($type != 'GET')
            $req = $this->browser->request($type, $url, $headers, $params);
        else
            $req = $this->browser->request($type, $url, $headers);


        // Added Request Timeout
        return timeout($req, $this->timeout)->then(
            function (ResponseInterface $response) {
                $decodedResponse = json_decode($response->getBody()->getContents(), true);
                return [
                    'result' => true,
                    'code' => $response->getStatusCode(),
                    'body' => $decodedResponse,
                ];
            },
            function ($error) {
                if ($error instanceof TimeoutException) {
                    return [
                        'result' => false,
                        'error' => $error->getMessage()
                    ];
                } else {
                    if ($error instanceof ResponseException) {
                        $response = $error->getResponse();
                        $decodedResponse = json_decode($response->getBody()->getContents(), true);
                        return [
                            'result' => true,
                            'code' => $response->getStatusCode(),
                            'body' => $decodedResponse,
                        ];
                    }
                    return [
                        'result' => false,
                        'error' => $error->getMessage()
                    ];
                }
            }
        );
    }
}