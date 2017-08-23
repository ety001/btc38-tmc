<?php
require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Libs\Api;

// config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$config = [
    'key' => getenv('key'),
    'skey' => getenv('skey'),
    'userid' => getenv('userid'),
    'apiurl' => getenv('apiurl')
];
// var_dump($config);
$orderConfig = [
    'maxtmc' => (double)getenv('maxtmc'),
    'maxcny' => (double)getenv('maxcny'),
    'buyrate' => (double)getenv('buyrate'),
    'sellrate' => (double)getenv('sellrate'),
    'amount' => (double)getenv('amount'),
    'orderval' => (double)getenv('orderval')
];

// create a log channel
$log = new Logger('tmc');
$log->pushHandler(new StreamHandler(__DIR__.'/logs/tmc.log', Logger::INFO));
// add records to the log
$log->info('');
$log->info('Time:', [date('Y-m-d H:i:s', time())]);
$log->info('OrderConfig:', $orderConfig);

$api = new Api($config);

// bid -- buy, ask -- sell
$price = $api->getPrice();
$log->info('Price:', $price);

$buyPrice = (double)number_format($price['bidPrice'] * (1 - $orderConfig['buyrate'] / 100), 3);
$sellPrice = (double)number_format($price['askPrice'] * (1 + $orderConfig['sellrate'] / 100), 3);
$log->info('BuySellPrice:', [$buyPrice, $sellPrice]);

$balance = $api->getBalance();
$log->info('Balance:', $balance);

$orderList = $api->getOrderList();
$log->info('OrderList:', $orderList);

$result = null;
if ($orderConfig['maxtmc'] < (double)$balance['tmc_balance']) {
    // 不操作
} else {
    // 判断是否下买单
    if ($orderConfig['amount'] * $buyPrice <= (double)$balance['cny_balance']) {
        // 买的起
        $currentBuyOrderCount = count($orderList['buy']);
        switch ($currentBuyOrderCount) {
            case 0:
                $log->info('CurrentBuyOrderCount:', [0]);
                $log->info('BuyOrder:', ['price'=>$buyPrice, 'amount'=>$orderConfig['amount']] );
                $buyResult = $api->submitOrder(1, $buyPrice, $orderConfig['amount']);
                $log->info('BuyOrderResult', [$buyResult]);
                break;
            case 1:
                $log->info('CurrentBuyOrderCount:', [1]);
                $tmp = $orderList['buy'];
                if ($buyPrice > (double)$tmp[0]['price']) {
                    // cancel order
                    $log->info('CancelOrder:', ['price'=>$buyPrice, 'oldPrice'=>$tmp[0]['price'], 'orderId'=>$tmp[0]['id']] );
                    $cancelResult = $api->cancelOrder($tmp[0]['id']);
                    $log->info('CancelOrderResult', [$cancelResult]);
                    // add new order
                    $log->info('BuyNewOrder:', ['price'=>$buyPrice, 'amount'=>$orderConfig['amount']] );
                    $buyResult = $api->submitOrder(1, $buyPrice, $orderConfig['amount']);
                    $log->info('BuyNewOrderResult', [$buyResult]);
                }
                break;
            default:
                $log->info('CurrentBuyOrderCount is much than 1');
                $nearestOrder = array_pop($orderList['buy']);
                // cancel other order
                foreach($orderList['buy'] as $k => $order) {
                    $log->info('CancelOtherOrder:', ['orderId'=>$order['id']] );
                    $cancelResult = $api->cancelOrder($order['id']);
                    $log->info('CancelOtherOrderResult', [$cancelResult]);
                }
                if ($buyPrice > (double)$nearestOrder['price']) {
                    // cancel order
                    $log->info('CancelNearestOrder:', ['price'=>$buyPrice, 'oldPrice'=>$nearestOrder['price'], 'orderId'=>$nearestOrder['id']] );
                    $cancelResult = $api->cancelOrder($nearestOrder['id']);
                    $log->info('CancelNearestOrderResult', [$cancelResult]);
                    // add new order
                    $log->info('BuyNewOrder:', ['price'=>$buyPrice, 'amount'=>$orderConfig['amount']] );
                    $buyResult = $api->submitOrder(1, $buyPrice, $orderConfig['amount']);
                    $log->info('BuyNewOrderResult', [$buyResult]);
                }
                break;
        }
    }
}

if ($orderConfig['maxcny'] < (double)$balance['cny_balance']) {
    // 不操作
} else {
    // 判断是否下卖单
    if ($orderConfig['amount'] <= (double)$balance['tmc_balance']) {
        // 有得卖
        $currentSellOrderCount = count($orderList['sell']);
        switch ($currentSellOrderCount) {
            case 0:
                $log->info('currentSellOrderCount:', [0]);
                $log->info('SellOrder:', ['price'=>$sellPrice, 'amount'=>$orderConfig['amount']] );
                $sellResult = $api->submitOrder(2, $sellPrice, $orderConfig['amount']);
                $log->info('SellOrderResult', [$sellResult]);
                break;
            case 1:
                $log->info('currentSellOrderCount:', [1]);
                $tmp = $orderList['sell'];
                if ($sellPrice < (double)$tmp[0]['price']) {
                    // cancel order
                    $log->info('CancelOrder:', ['price'=>$sellPrice, 'oldPrice'=>$tmp[0]['price'], 'orderId'=>$tmp[0]['id']] );
                    $cancelResult = $api->cancelOrder($tmp[0]['id']);
                    $log->info('CancelOrderResult', [$cancelResult]);
                    // add new order
                    $log->info('SellNewOrder:', ['price'=>$sellPrice, 'amount'=>$orderConfig['amount']] );
                    $sellResult = $api->submitOrder(2, $sellPrice, $orderConfig['amount']);
                    $log->info('SellNewOrderResult', [$sellResult]);
                }
                break;
            default:
                $log->info('currentSellOrderCount is much than 1');
                $nearestOrder = array_pop($orderList['sell']);
                // cancel other order
                foreach($orderList['sell'] as $k => $order) {
                    $log->info('CancelOtherOrder:', ['orderId'=>$order['id']] );
                    $cancelResult = $api->cancelOrder($order['id']);
                    $log->info('CancelOtherOrderResult', [$cancelResult]);
                }
                if ($sellPrice < (double)$nearestOrder['price']) {
                    // cancel order
                    $log->info('CancelNearestOrder:', ['price'=>$sellPrice, 'oldPrice'=>$nearestOrder['price'], 'orderId'=>$nearestOrder['id']] );
                    $cancelResult = $api->cancelOrder($nearestOrder['id']);
                    $log->info('CancelNearestOrderResult', [$cancelResult]);
                    // add new order
                    $log->info('BuyNewOrder:', ['price'=>$sellPrice, 'amount'=>$orderConfig['amount']] );
                    $sellResult = $api->submitOrder(2, $sellPrice, $orderConfig['amount']);
                    $log->info('BuyNewOrderResult', [$sellResult]);
                }
                break;
        }
    }
}

// buy
// $price = 4;
// $amount = 1;
// $coinname = 'tmc';
// $result = $api->submitOrder(1, $price, $amount, $coinname);

// sell
// $price = 14;
// $amount = 1;
// $coinname = 'tmc';
// $result = $api->submitOrder(2, $price, $amount, $coinname);

// cancel order
// $orderId = '368168415';
// $result = $api->cancelOrder($orderId);