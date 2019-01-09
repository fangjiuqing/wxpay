<?php
/**
 * unifiedorder 统一下单
 * 应用场景
 * 除付款码支付场景以外，商户系统先调用该接口在微信支付服务后台生成预支付交易单，
 * 返回正确的预支付交易会话标识后再按Native、JSAPI、APP等不同场景生成交易串调起支付
 *
 * URL地址：https://api.mch.weixin.qq.com/pay/unifiedorder
 */
include 'config.php';
include 'overSeaPay.php';

$unified_gateway = "https://api.mch.weixin.qq.com/pay/unifiedorder";

// 重置跳转地址
$config['redirect_uri'] = 'http://qifanonline.com/wxpay/andy/code_pay.php';
$oop = new overSeaPay($config);
$config['sub_openid'] = $oop->getOpenid();
$params = array(
    "appid"      => $config['appid'],
    "mch_id"     => $config['mch_id'],//aapay
    "sub_mch_id" => $config['sub_mch_id'],//qibee
    "sub_appid"  => $config['sub_appid'],
    "nonce_str"  => mt_rand(1000000,2000000),
    "body"       => "Ipad Mini 2 64G Celler",
    "out_trade_no" => date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)."",
    "total_fee"  => 10,
    "fee_type"   => "HKD",//usd
    "spbill_create_ip" =>  $_SERVER['REMOTE_ADDR'],
    //"notify_url"=> "https://www.ipasspaytest.biz/index.php/Thirdpay/Wxpay/notifyUrl",
    "notify_url"=> $config['notify_url'],
    "trade_type"=> "NATIVE",
    "sub_openid"     => $config['sub_openid'],
    // 此处不可自定义参数
    // "custom_mid"     => '201821231', 
   // "custom_appid"   => '219378429912489232',  
);


//$unified_gateway = "https://apihk.mch.weixin.qq.com/pay/unifiedorder";
$string         = $oop->ASCII($params);
$params["sign"] = $oop->getSign($string); //Section 5.3.1 Signature Algorithm.
$xmlData        = $oop->arrayToXml($params);
$curlData = $oop->curl($xmlData,$unified_gateway);
$response = $oop->xmlToArray($curlData);

// 成功
if ($response["return_code"] == "SUCCESS") {
    $code_url   = $response["code_url"];
} else { // 失败
    // TODO 入库
    //return $response["return_msg"];
    $parameters = $response["return_msg"];
}
?>

<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>微信支付样例-NATIVE扫码支付</title>
</head>
<body>
<table style="width:100%; border:1px solid #ccc">
    <tr>
        <td>参数名</td>
        <td>参数值</td>
    </tr>
    <?php foreach ($params as $k => $v) { ?>
    <tr>
        <td><?php echo $k; ?></td>
        <td><?php echo $v; ?></td>
    </tr>
   <?php }?>
</table>
<div align="center" style="margin:20px 0px; padding:0px 20px;">
    <img alt="模式一扫码支付" src="qrcode.php?data=<?php echo urlencode($code_url);?>" style="width:150px;height:150px;"/>
</div>
</body>
</html>
