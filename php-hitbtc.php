<?php
//include dirname(__FILE__)."/php-binance-api-rate-limiter.php";
include dirname(__FILE__)."/php-hitbtc-api.php";
require 'vendor/autoload.php';
$b_key = 'J61qpAOoLxKICjxVUb2xIuoZJOyRc8ADSOLFVGV4y9TcBqdhw1ffve5KzARCTbvV';
$b_secr = 'PdX4OPkyAFknpywtooIKPjIdN1GFgotCkStxVZUEB84aDuVyermz3rcOQkulWUBj';
$api = new Hitbtc\API($b_key,$b_secr);

$loop = React\EventLoop\Factory::create();
$connector = new Ratchet\Client\Connector($loop);

$count = 0;
$start = new DateTime('2011-01-01T15:03:01.012345Z');
$start->setTimestamp(time());
$curtime = new DateTime('2011-01-01T15:03:01.012345Z');
$curtime->setTimestamp(time());

$connector('wss://st.hitbtc.com/')
->then(function(Ratchet\Client\WebSocket $conn) {
    $c = '[1, "s", []]';
    $rtn = $conn->send($c);

    $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
        global $count ;
        global $trade, $start, $curtime;
        
        $curtime->setTimestamp(time());
        $interval = $curtime->diff($start);
        
        
        $a = substr($msg, 1, 1000);
        $data = json_decode($msg, true);
        if ( $data == 'pong')
        {
            printf(">>>> recv pong\n");
            return;
        }
        if ( $data[0] =='2' && $data[1] == 's')
        {
            $b = $data[2];
            getTrade($b);
        }

        // ping send
        if ( ($count ++)  > 10)
        {
            printf(">>>> send ping\n");
            $rtn = $conn->send('ping');
            $count = 0;
        }
        
        if ( false)
        {
            $data = json_decode($msg, true);
            $a = $data[2];
            $i = 0;
            foreach($a as $val)
            {
                $i ++;
                printf("%05d %s\n", $i,print_r($val,true) );
                
            }
        }
        
        
        printf("Loop. %d \t Time %s(UTC), Running Time %s\n", $count, $curtime->format('H:i:s'),  $interval->format('%m-%d %H:%I:%S') );
        
    });
            
                
    $conn->on('close', function($code = null, $reason = null) {
        echo "Connection closed ({$code} - {$reason})\n";
    });
}, function(\Exception $e) use ($loop) {
    echo "Could not connect: {$e->getMessage()}\n";
    $loop->stop();
});
    
$loop->run();

function getTrade($b)
{
    $keys = array('symbol', 'close','low', 'high', 'quoteVolume', 'volume');
    extract($keys);
    foreach($b as $v)
    {
        list($symbol, $close, $low, $high, $quoteVolume, $volume) = $v;
        $symbol = chgsymbol($symbol);
        printf("symbol:%s, close:%s, low:%s, high:%s, quoteVolume:%s, volume:%s\n"
                ,$symbol, $close,$low, $high, $quoteVolume, $volume);
    }
}

function chgsymbol($str)
{
        $arr = array();
        $arr[] = '/(.*)(BTC)/m';
        $arr[] = '/(.*)(ETH)/m';
        $arr[] = '/(.*)(USDT)/m';
        $arr[] = '/(.*)(BNB)/m';
        $arr[] = '/(.*)(USD)/m';

        $rtn = preg_replace($arr, '\1/\2',$str);
        if ( $rtn[0] == '/') $rtn = substr($rtn, 1);
        return $rtn;

}