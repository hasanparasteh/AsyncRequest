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
            function (ResponseInterface $response) use ($canResponseDecode) {
                return [
                    'result' => true,
                    'code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => $canResponseDecode
                        ? json_decode($response->getBody()->getContents(), true)
                        : $response->getBody()->getContents()
                ];
            },
            function ($error) use ($canResponseDecode) {
                if ($error instanceof ResponseException) {
                    $response = $error->getResponse();
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
}