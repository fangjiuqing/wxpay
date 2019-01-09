<?php
/**
 * 线下支付 金额输入以及商家用户确认
 */

include 'config.php';
include 'overSeaPay.php';

$mid   = isset($_GET['mid']) ? $_GET['mid'] : 0;
$appid = isset($_GET['appid']) ? $_GET['appid'] : 0;

$config['redirect_uri']    =    'http://qifanonline.com/wxpay/andy/merchant.php?mid=' . $mid . '&appid=' . $appid;
$oop = new overSeaPay($config);
$config['sub_openid'] = $oop->getOpenid();


?>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>线下支付样例</title>
</head>
<body>
    <form action="store_pay.php" method="post">
	    <br/><br/>
	    <div>
	    	<p>
	    		<font color="#9ACD32"><b>请输入支付金额<span style="color:#f00;font-size:50px"><input type="number" name="amount"></span>分</b></font>
	    	</p>
	    </div>
	    <div align="center">
	        <input type="hidden" name="merch_id" value="<?php echo $mid; ?>" />
	        <input type="hidden" name="merch_appid" value="<?php echo $appid; ?>" />
	        <input type="hidden" name="sub_openid" value="<?php echo $config['sub_openid']; ?>" />
	        <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="submit" >提交</button>
	    </div>
    </form>
</body>

</html>