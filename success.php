<?php

include '../../../wp-load.php';
$result = $_POST;
//var_dump($result);
//exit;

if ($result['TranCode'] == '000') {
    if (isset($_SESSION['return_url'])) {
        $return_url = $_SESSION['return_url'];
        //$return_url = 'http://popartcode.space/akumulator-shop/korpa/order-received/300?key=wc_order_59afa3fe6ccb0';
        //var_dump($return_url);
        //unset($_SESSION['return_url']);
        add_post_meta($_SESSION['order_id'], 'ID naloga', $result['OrderID']);
        $order = wc_get_order($_SESSION['order_id']);
        $order->update_status('completed', __('Payment from Raiffeisen received and stock has been reduced', 'wc-raiffeisen-payment'));
        $order->reduce_order_stock();
        //$order->payment_complete();

        $invoice = new WC_Email_Customer_Invoice();
        $invoice->trigger($_SESSION['order_id']);

        WC()->cart->empty_cart();
        /* echo '<pre>';
          var_dump($_SESSION);
          echo '</pre>'; */
        unset($_SESSION['return_url']);
        unset($_SESSION['total']);
        unset($_SESSION['order_id']);
        wp_redirect($return_url);
    } else {
        //unset($_SESSION['return_url']);
        wp_redirect(home_url('/'));
    }
} else {

}
