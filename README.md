PayPal PHP Merchant API SDK
===========================

Simple PHP library for working with PayPal's ExpressCheckout and basic transaction handling.

Requirements
------------

* curl
* PHP 5.3+
* PayPal Sandbox account
    
Usage
-----

See below for basic usage examples. More examples can be found in the tests folder.
      
Express Checkout Example:
    <?php
    require_once 'paypal_php_sdk/PayPal.php'; 
    define("PAYPAL_API_USERNAME", "your API username");
    define("PAYPAL_API_PASSWORD", "your API password");
    define("PAYPAL_API_SIGNATURE", "your API signature");
    define("PAYPAL_SANDBOX", true);
    $paypal = new PayPalExpressCheckout;
    $response = $paypal->setEC('45.95', 'http://example.com/checkout/review/', 'http://example.com/cart/');
    if ($response->status == 'Success') $checkoutToken = $response->token;
    ?>
    
