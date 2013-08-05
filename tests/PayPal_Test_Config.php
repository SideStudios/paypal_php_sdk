<?php
/**
 * Tests for the PayPal PHP SDK
 */

/**
 * Enter your test account credentials to run tests against sandbox.
 */
define("PAYPAL_API_USERNAME", "foxxyz_api1.gmail.com");
define("PAYPAL_API_PASSWORD", "1375208217");
define("PAYPAL_API_SIGNATURE", "AXdg-8KGX4KRN.WbIvoGaAtcQMGdARPdf5bCoYaeU6TrNeUT1EQnVDuF");
define("PAYPAL_SANDBOX", true);


define("PAYPAL_LOG_FILE", dirname(__FILE__) . "/log");
// Clear logfile
file_put_contents(PAYPAL_LOG_FILE, "");

if (!function_exists('curl_init')) {
    throw new Exception('The PayPal SDK needs the CURL PHP extension.');
}

require_once dirname(dirname(__FILE__)) . '/PayPal.php';

if (PAYPAL_API_USERNAME == "") {
    die('Enter your PayPal test credentials in '.__FILE__.' before running the test suite.');
}
