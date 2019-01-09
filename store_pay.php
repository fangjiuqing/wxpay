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
    "total_fee"  => $_POST['amount'],
    "fee_type"   => "HKD",//usd
    "spbill_create_ip" =>  $_SERVER['REMOTE_ADDR'],
    //"notify_url"=> "https://www.ipasspaytest.biz/index.php/Thirdpay/Wxpay/notifyUrl",
    "notify_url"=> $config['notify_url'],
    "trade_type"=> "JSAPI",
    "sub_openid"     => $config['sub_openid'],
);

//$params['sub_openid'] = $oop->getOpenid();

//$unified_gateway = "https://apihk.mch.weixin.qq.com/pay/unifiedorder";
$string         = $oop->ASCII($params);
$params["sign"] = $oop->getSign($string); //Section 5.3.1 Signature Algorithm.
$xmlData        = $oop->arrayToXml($params);
$curlData = $oop->curl($xmlData,$unified_gateway);
$response = $oop->xmlToArray($curlData);


# 拼接自己的参数
$my_params = [
    'mid'    =>    $_POST['mid'],
    'appid'  =>    $_POST['appid'],
];


// 成功
if ($response["return_code"] == "SUCCESS") {
    $paramsOrder = array(
        "appId"     => "wx39963eb0c927fc5e",
        "nonceStr"  => mt_rand(1000000,2000000)."",
        "package"   => "prepay_id=".$response["prepay_id"],
        "signType"  => "MD5",
        "timeStamp" => time()."",
    ) ;

    $stringOrder = $oop->ASCII($paramsOrder);
    $paramsOrder["paySign"] = $oop->getSign($stringOrder); //Section 5.3.1 Signature Algorithm.
    $jsapi->values['appId'] = $paramsOrder["appId"];
    $jsapi->values['timeStamp'] = (string)$paramsOrder["timeStamp"];
    $jsapi->values['nonceStr'] = $paramsOrder["nonceStr"];
    $jsapi->values['package'] = $paramsOrder["package"];
    $jsapi->values['signType'] = $paramsOrder["signType"];
    $jsapi->values['paySign'] = $paramsOrder["paySign"];
    $parameters = json_encode($jsapi->values);

    $my_params = array_merge($response , $my_params);
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
    <title>线下商户确认金额支付-JSAPI方式</title>
    <script type="text/javascript">
        //调用微信JS api 支付
        function jsApiCall()
        {
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                <?php echo $parameters; ?>,
                function(res){
                    WeixinJSBridge.log(res.err_msg);
                    //
                    alert('custom js ' + JSON.stringify(res));
                }
            );
        }

        function callpay()
        {
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            }else{
                jsApiCall();
            }
        }
    </script>
    <script type="text/javascript">
        //获取共享地址
        function editAddress()
        {
            WeixinJSBridge.invoke(
                'editAddress',
                <?php echo $editAddress; ?>,
                function(res){
                    var value1 = res.proviceFirstStageName;
                    var value2 = res.addressCitySecondStageName;
                    var value3 = res.addressCountiesThirdStageName;
                    var value4 = res.addressDetailInfo;
                    var tel = res.telNumber;

                    alert(value1 + value2 + value3 + value4 + ":" + tel);
                }
            );
        }

        window.onload = function(){
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', editAddress, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', editAddress);
                    document.attachEvent('onWeixinJSBridgeReady', editAddress);
                }
            }else{
                editAddress();
            }
        };


    </script>
</head>
<body>
<table style="width:100%; border:1px solid #ccc">
    <tr>
        <td>参数名</td>
        <td>参数值</td>
    </tr>
    <?php foreach ($my_params as $k => $v) { ?>
    <tr>
        <td><?php echo $k; ?></td>
        <td><?php echo $v; ?></td>
    </tr>
   <?php }?>
</table>
<div align="center" style="margin:20px 0px; padding:0px 20px;">
    <p>支付金额：<b style="color: red"><?php echo $_POST['amount']; ?></b></p>
    <button style="width:40%x; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >确认支付</button>
    
</div>
</body>
</html>
