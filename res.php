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

// create a log channel
#$log = new Logger('res');
#$log->pushHandler(new StreamHandler(__DIR__.'/logs/res.log', Logger::INFO));
#// add records to the log
#$log->info('');
#$log->info('Time:', [date('Y-m-d H:i:s', time())]);
#$log->info('OrderConfig:', $orderConfig);

$api = new Api($config);
$tradeList = $api->getMyTradeList();
$condition = strtotime('2017-08-22 00:00:00');

$result = [
    'buy' => [],
    'sell' => [],
];
$sellTotal = 0; // 成交金额
$sellAmount = 0; // 成交数量
$buyTotal = 0; // 成交金额
$buyAmount = 0; // 成交数量
$page = 0;
do {
    $continued = true;
    $tradeList = $api->getMyTradeList($page++);
    foreach($tradeList as $k => $trade) {
        if (strtotime($trade['time']) > $condition) {
            if ($trade['buyer_id'] == '0') {
                // sell
                array_push($result['sell'], $trade);
                $sellTotal += ($trade['volume'] * $trade['price']);
                $sellAmount += $trade['volume'];
            } else {
                // buy
                array_push($result['buy'], $trade);
                $buyTotal += ($trade['volume'] * $trade['price']);
                $buyAmount += $trade['volume'];
            }
        } else {
            $continued = false;
            break;
        }
    }
} while($continued);

printf("SellTotal: %f,\nSellAmount: %f\n", $sellTotal, $sellAmount);
printf("BuyTotal: %f,\nBuyAmount: %f\n", $buyTotal, $buyAmount);
printf("Add Amount:%f, Add Total: %f, avg Price: %f\n", $buyAmount-$sellAmount, $buyTotal-$sellTotal, ($buyTotal-$sellTotal) / ($buyAmount-$sellAmount) );

// var_dump(array_pop($result['buy']), array_pop($result['sell']));
