<?php
include '../../../wp-load.php';

$settings = get_option('woocommerce_raiffeisen_gateway_settings');

$MerchantID = $settings['merchantid'];
$TerminalID = $settings['terminalid'];
$gatewayAddress = $settings['gatewayAddress'];
//$site_url = get_stylesheet_directory_uri();
$plugin_url = __DIR__ . '/pem/' . $MerchantID . '.pem';
$OrderID = 21 . date("ymdHis");
$PurchaseTime = date("ymdHis");
if (isset($_SESSION['total'])) {
    //var_dump($_SESSION['total']);
    $TotalAmount = str_replace('.', '', $_SESSION['total']);
    //var_dump($TotalAmount);
}
//var_dump($_SESSION['return_url']);
$CurrencyID = $settings['currency'];
//$delay = '1';
$data = "$MerchantID;$TerminalID;$PurchaseTime;$OrderID;$CurrencyID;$TotalAmount;;";
$fp = fopen($plugin_url, "r");
$priv_key = fread($fp, 8192);
fclose($fp);
$pkeyid = openssl_get_privatekey($priv_key);
openssl_sign($data, $signature, $pkeyid);
openssl_free_key($pkeyid);
$b64sign = base64_encode($signature);
?>




<form id="credit-card-payment" action="<?php echo $gatewayAddress; ?>" method="post" >
    <input name="Version" type="hidden" value="1" />
    <input name="MerchantID" type="hidden" value="<?php echo $MerchantID; ?>" />
    <input name="TerminalID" type="hidden" value="<?php echo $TerminalID; ?>" />
    <input name="TotalAmount" type="hidden" value="<?php echo $TotalAmount; ?>" />
    <input name="Currency" type="hidden" value="<?php echo $CurrencyID; ?>" />
    <input name="locale" type="hidden" value="rs" />
    <input name="PurchaseTime" type="hidden" value="<?php echo $PurchaseTime ?>" />
    <input name="OrderID" type="hidden" value="<?php echo $OrderID ?>" />
    <input name="Signature" type="hidden" value="<?php echo "$b64sign" ?>"/>
    <!--<input type="submit" id='credit-card-payment'/>-->
</form>


<script type="text/javascript">
    document.getElementById('credit-card-payment').submit();
</script>