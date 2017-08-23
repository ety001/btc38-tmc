<?php
namespace Libs;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Api
{
    private $config;
    private $client;
    private $privateKey;
    public function __construct($config)
    {
        $this->config = $config;
        $this->privateKey = 
            $config['key'].'_'
            .$config['userid'].'_'
            .$config['skey'].'_';
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

    public function getBalance() {
        $url = 'getMyBalance.php';
        $timestamp = time();
        $postData = [
            'key' => $this->config['key'],
            'time' => $timestamp,
            'md5' => md5($this->privateKey . $timestamp)
        ];
        // var_dump($postData);
        $response = $this->client->post($url, [
            'form_params' => $postData
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK
        $body = $response->getBody();
        $remainingBytes = $body->getContents();
        if ($code === 200) {
            $balances = json_decode($remainingBytes, true);
            return [
                'cny_balance' => $balances['cny_balance'],
                'tmc_balance' => $balances['tmc_balance']
            ];
        } else {
            return false;
        }
    }

    public function getOrderList($c='tmc', $mkType='cny') {
        $url = 'getOrderList.php';
        $timestamp = time();
        $postData = [
            'key' => $this->config['key'],
            'time' => $timestamp,
            'md5' => md5($this->privateKey . $timestamp),
            'mk_type' => $mkType,
            'coinname' => $c
        ];
        // var_dump($postData);
        $response = $this->client->post($url, [
            'form_params' => $postData
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK
        $body = $response->getBody();
        $remainingBytes = $body->getContents();
        $result = [
            'buy' => [],
            'sell' => []
        ];
        if ($code === 200) {
            $orderList = json_decode($remainingBytes, true);
            if ($orderList) {
                foreach ($orderList as $k => $order) {
                    if ($order['type'] == 1) {
                        // buy
                        array_push($result['buy'], $order);
                    } else if ($order['type'] == 2) {
                        // sell
                        array_push($result['sell'], $order);
                    }
                }
            }
            return $result;
        } else {
            return false;
        }
    }

    /**
     * $type: 1buy 2sell
     * $result: succ|123, overBalance
     */
    public function submitOrder($type=null, $price=null, $amount=null, $coinname='tmc', $mkType='cny') {
        if (!$type || !$price || !$amount || !$coinname) {
            return false;
        }
        $url = 'submitOrder.php';
        $timestamp = time();
        $postData = [
            'key' => $this->config['key'],
            'time' => $timestamp,
            'md5' => md5($this->privateKey . $timestamp),
            'type' => $type,
            'mk_type' => $mkType,
            'price' => $price,
            'amount' => $amount,
            'coinname' => $coinname
        ];
        // var_dump($postData);
        $response = $this->client->post($url, [
            'form_params' => $postData
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK
        $body = $response->getBody();
        $remainingBytes = $body->getContents();
        $result = explode('|', $remainingBytes);
        return $result;
    }

    /**
     * $result: succ, overtime
     */
    public function cancelOrder($orderId=null, $coinname='tmc', $mkType='cny') {
        $url = 'cancelOrder.php';
        $timestamp = time();
        $postData = [
            'key' => $this->config['key'],
            'time' => $timestamp,
            'md5' => md5($this->privateKey . $timestamp),
            'mk_type' => $mkType,
            'order_id' => $orderId,
            'coinname' => $coinname
        ];
        // var_dump($postData);
        $response = $this->client->post($url, [
            'form_params' => $postData
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK
        $body = $response->getBody();
        $remainingBytes = $body->getContents();
        return $remainingBytes;
    }
}