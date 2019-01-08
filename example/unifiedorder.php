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

$unified_gateway = "https://api.mch.weixin.qq.com/pay/unifiedorder";

$oop = new overSeaPay($config);

$params = array(
    "appid"      => $config['appid'],
    "mch_id"     => $config['mch_id'],//aapay
    "sub_mch_id" => $config['sub_mch_id'],//qibee
    "sub_appid"  => $config['sub_appid'],
    "nonce_str"  => 'Qibey' . mt_rand(1000000,2000000) . 'LTD',
    "body"       => "test",
    "out_trade_no" => date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)."",
    "total_fee"  => 100,
    "fee_type"   => "HKD",//usd
    "spbill_create_ip" =>  $_SERVER['REMOTE_ADDR'],
    //"notify_url"=> "https://www.ipasspaytest.biz/index.php/Thirdpay/Wxpay/notifyUrl",
    "notify_url"=> $config['notify_url'],
    "trade_type"=> "JSAPI",
    //"sub_openid"=> $oop->getOpenid(),
    "sub_openid"=> $config['sub_openid'],
);
//$unified_gateway = "https://apihk.mch.weixin.qq.com/pay/unifiedorder";
$string         = $oop->ASCII($params);
$params["sign"] = $oop->getSign($string); //Section 5.3.1 Signature Algorithm.
$xmlData        = $oop->arrayToXml($params);
$curlData = $oop->curl($xmlData,$unified_gateway);
$response = $oop->xmlToArray($curlData);

// 成功
if ($response["return_code"] == "SUCCESS") {
    $paramsOrder = array(
        "appId" => "wx39963eb0c927fc5e",
        "nonceStr" => mt_rand(1000000,2000000)."",
        "package" => "prepay_id=".$response["prepay_id"],
        "signType" => "MD5",
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


class overSeaPay
{
    //支付网关-大陆服务器
    private $gateway_china = "https://api.mch.weixin.qq.com/pay/micropay";
    //支付网关-香港服务器
    private $gateway_asian = "https://apihk.mch.weixin.qq.com";
    //撤销订单网关
    private $revoke_gateway= "https://api.mch.weixin.qq.com/secapi/pay/reverse";

    //退款网关
    //private $refund_gateway = "https://payment.goopay.cn/onlinerefund";
    //private $refund_gateway = "https://payment.fhtpay.com/FHTPayment/api/debit";
    //查询网关
    //private $query_gateway = "https://payment.goopay.cn/onlineQuery";
    private $mch_id;
    private $key;
    private $sub_mch_id;
    private $appid;
    private $secret;
    //private $redirect_uri = 'https://ipasspay.xyz/afonso/wxpay/demo/wxpay.php';
    //private $redirect_uri = 'https://qifanonline.com/wxpay/wxpay.php';
    private $redirect_uri;
    private $sub_openid;

    public function __construct( $config = []) {
        $this->mch_id       = $config['mch_id'];
        $this->key          = $config['key'];
        $this->sub_mch_id   = $config['sub_mch_id'];
        $this->appid        = $config['sub_appid'];
        $this->secret       = $config['secret'];
        $this->redirect_uri = $config['redirect_uri'];
        $this->sub_openid   = $config['sub_openid'];
    }

    //自定义ascii排序
    public function ASCII($params = array()){
        if(!empty($params)){
            $p =  ksort($params);
            if($p){
                $str = '';
                foreach ($params as $k=>$val){
                    if ($k != "sign") {
                        $str .= $k .'=' . $val . '&';
                    }
                }
                $strs = rtrim($str, '&');
                return $strs;
            }
        }
        return '参数错误';
    }

    // Join API keys
    public function getSign($string){
        $stringSignTemp = $string."&key=".$this->key;
        $sign = strtoupper(md5($stringSignTemp));
        return $sign;
    }

    public function getOpenid() {
        // 有code返回值
        if (isset($_GET['code'])) {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $redirect = "https://api.wechat.com/sns/oauth2/access_token?appid=" . $this->appid . "&secret=" . $this->secret . "&code=" . $code . "&grant_type=authorization_code";
            $result = $this->http_get($redirect);
            $result = json_decode($result, true);
            $openid = $result['openid'];
            return $openid;
            //}
        } else {
            //触发微信返回code码
            //$redirect = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appid . "&redirect_uri=" . urlencode($this->redirect_uri) . "&response_type=code&scope=snsapi_base&state=STATE&connect_redirect=1#wechat_redirect";
            $redirect = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $this->appid . "&redirect_uri=" . urlencode($this->redirect_uri) . "&response_type=code&scope=snsapi_base&state=" . $sub_openid ."&connect_redirect=1#wechat_redirect";
            Header("Location: $redirect");
            exit();
        }


    }

    // 数组转化为XML格式
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if(is_array($val)){
                $xml.="<".$key.">".arrayToXml($val)."</".$key.">";
            }else{
                $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    // XML[CDATA]格式转化为数组
    public function xmlToArray($arr){
        $objectxml = simplexml_load_string($arr, 'SimpleXMLElement', LIBXML_NOCDATA);//将文件转换成对象
        $xmljson= json_encode($objectxml );//将对象转换个JSON
        $xmlarray=json_decode($xmljson,true);//将json转换成数组
        return $xmlarray;
    }
    // curl请求
    public function curl($xmlData,$url){
        //第一种发送方式，也是推荐的方式：
        $header[] = "Content-type: text/xml";      //定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $xmlData);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function wxqrcode($url) {
        include "phpqrcode.php";
        $errorCorrectionLevel = "L";
        $matrixPointSize = "8";
        QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize);
    }

    // 微信浏览器下get请求
    public function http_get($url)
    {
        $curlVersion = curl_version();
        $ua = "WXPaySDK/3.0.9 (".PHP_OS.") PHP/".PHP_VERSION." CURL/".$curlVersion." "
            .$this->sub_mch_id;

        $headerArray = array(
            'Accept:application/json, text/javascript, */*',
            'Content-Type:application/x-www-form-urlencoded',
            'Referer:https://mp.weixin.qq.com/'
        );
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl,CURLOPT_USERAGENT,$ua);//设置用户代理
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);//设置头信息
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;    //返回json对象

    }
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
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                <?php echo $parameters; ?>,
                function(res){
                    WeixinJSBridge.log(res.err_msg);
                    //
                    alert(res.err_code+res.err_desc+res.err_msg);
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
<br/>
<font color="#9ACD32"><b>该笔订单支付金额为<span style="color:#f00;font-size:50px"> <?php echo 0.01;?></span>元</b></font><br/><br/>
<div align="center">
    <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay()" >立即支付</button>
</div>
</body>
</html>