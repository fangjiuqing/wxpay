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
//$config['sub_openid'] = 'o8wR60-6nxIoFj5ZOVoucSWwn_gw';//L wechat open id
  $config['sub_openid'] = $oop->getOpenid();
//var_dump($config['sub_openid']);die;
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
    "trade_type"=> "JSAPI",
    "sub_openid"     => $config['sub_openid'],
    // 此处不可自定义参数
    // "custom_mid"     => '201821231', 
   // "custom_appid"   => '219378429912489232',  
);

//$params['sub_openid'] = $oop->getOpenid();

//$unified_gateway = "https://apihk.mch.weixin.qq.com/pay/unifiedorder";
$string         = $oop->ASCII($params);
$params["sign"] = $oop->getSign($string); //Section 5.3.1 Signature Algorithm.
$xmlData        = $oop->arrayToXml($params);
$curlData = $oop->curl($xmlData,$unified_gateway);
$response = $oop->xmlToArray($curlData);

var_dump($response);die;
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
    //echo $parameters;
    //var_dump($paramsOrder);
    //exit;
    //return $parameters;


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
    <title>微信支付样例-支付</title>
    <script type="text/javascript">
        //调用微信JS api 支付
        function jsApiCall()
        {
            // alert(JSON.stringify(<?php echo $parameters; ?>)); //$params参数 对象形式
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
    <?php foreach ($params as $k => $v) { ?>
    <tr>
        <td><?php echo $k; ?></td>
        <td><?php echo $v; ?></td>
    </tr>
   <?php }?>
</table>
<div align="center" style="margin:20px 0px; padding:0px 20px;">
    <button style="width:40%x; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >JSAPI支付</button>
    <button style="width:40%x; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >扫码支付</button>
</div>
</body>
</html>
