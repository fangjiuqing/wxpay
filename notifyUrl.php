<?php
file_put_contents('test.txt' , date('Y-m-d H:i:s'));

ob_start();
$data = file_get_contents('php://input');
echo '<pre>';
print_r($data);
file_put_contents('simple_logs.txt' , ob_get_contents());



die;
include 'config.php';
require "overSeaPay.php";

$xml = file_get_contents("php://input");

$oop = new overSeaPay($config);
$xmlArray = $oop->xmlToArray($xml);
$stringOrder = $oop->ASCII($xmlArray);
$verifySign = $oop->getSign($stringOrder); //Section 5.3.1 Signature Algorithm.
$sign = $xmlArray["sign"]; //Section 5.3.1 Signature Algorithm.
file_put_contents('logVerifySign.txt','TIME:' . date('Y-m-d H:i:s') . $verifySign . '------------------------' . PHP_EOL);
file_put_contents('logsign.txt','TIME:' . date('Y-m-d H:i:s') . $sign . '---------------------' . PHP_EOL);
file_put_contents('log.txt','TIME:' . date('Y-m-d H:i:s') . $xml . '----------------------' . PHP_EOL);
