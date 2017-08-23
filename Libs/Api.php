<?php
namespace Libs;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Api
{
    private $config;
    private $client;
    public function __construct($config)
    {
        $this->config = $config;
        $this->client = new Client(['base_uri' => $this->config['apiurl']]);
    }

    public function getDepth($c='tmc', $mkType='cny') {
        // http://api.btc38.com/v1/depth.php?c=ltc&mk_type=cny
        $url = "depth.php?c={$c}&mk_type={$mkType}";
        // Send a request to https://foo.com/api/test
        $response = $this->client->get($url);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK
        $body = $response->getBody();

        if($code === 200) {
            $result = json_decode($body, true);
            // var_dump($result);
            return $result;
        } else {
            return false;
        }
    }

    public function getPrice($c='tmc', $mkType='cny') {
        $result = $this->getDepth($c, $mkType);
        if($result) {
            return [
                'bidPrice' => $result['bids'][0][0],
                'askPrice' => $result['asks'][0][0]
            ];
        } else {
            return false;
        }
    }
}