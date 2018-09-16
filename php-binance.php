<?php
//include dirname(__FILE__)."/php-binance-api-rate-limiter.php";
include dirname(__FILE__)."/php-binance-api.php";
require 'vendor/autoload.php';
$b_key = 'J61qpAOoLxKICjxVUb2xIuoZJOyRc8ADSOLFVGV4y9TcBqdhw1ffve5KzARCTbvV';
$b_secr = 'PdX4OPkyAFknpywtooIKPjIdN1GFgotCkStxVZUEB84aDuVyermz3rcOQkulWUBj';
$api = new Binance\API($b_key,$b_secr);

$count = 0;
$trade = array();

$prices = $api->prices();
$time = time();
foreach($prices as $symbol => $price)
{
        $trade[$symbol] = array( 'eventTime'    => $time
                                , 'symbol'      => $symbol
                                , 'close'       => $price
                                , 'seq'         => 0);

}

$start = new DateTime('2011-01-01T15:03:01.012345Z');
$start->setTimestamp(time());
$api->miniTicker( function ( $api, $ticker ) use ( &$count ) {
        global $trade, $start;
        $d = new DateTime('2011-01-01T15:03:01.012345Z');
        foreach( $ticker as $val)
        {
                $val['seq'] = $count;
                $trade[$val['symbol']] = $val;
        }

        foreach( $trade as $val)
        {
                $t[$val['close']] = $val;
        }

        ksort($t);
        echo "\n---------------------------------------------------------------------------------------------\n";
        $index = 0;
        foreach( $t as $val)
        {
                extract($val);
                if ( $seq != $count) continue;
                $d->setTimestamp( $eventTime/1000 );
                $u = $eventTime - (intval($eventTime/1000)*1000);
                $d->setTime( $d->format('H'), $d->format('i'), $d->format('s'), $u);
                //printf("time %s : symbol %s => close %s\n", date("Y-m-d H:i:s.u", $eventTime), $symbol, $close);
                printf("Binance %s \t %02d.%-10s close % 20.8f(%d)\n",$d->format("Y-m-d h:i:s.u"), $index++, chgsymbol($symbol), $close, $seq);
        }

        $d->setTimestamp(time());
        $interval = $d->diff($start);
   printf("Loop. %d \t Time %s(UTC), Running Time %s", $count, $d->format('H:i:s'),  $interval->format('%m-%d %H:%I:%S') );
   $count++;
   /*
   if($count > 1000) {
      $endpoint = '@miniticker';
      $api->terminate( $endpoint );
   }
        */
} );

function chgsymbol($str)
{
        $arr = array();
        $arr[] = '/(.*)(BTC)/m';
        $arr[] = '/(.*)(ETH)/m';
        $arr[] = '/(.*)(USDT)/m';
        $arr[] = '/(.*)(BNB)/m';

        $rtn = preg_replace($arr, '\1/\2',$str);
        if ( $rtn[0] == '/') $rtn = substr($rtn, 1);
        return $rtn;

}