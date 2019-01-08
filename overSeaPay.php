<?php
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
        if ( is_array($curlVersion) && isset($curlVersion['version_number'])){
            $curlVersion = $curlVersion['version_number'];
        }
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
