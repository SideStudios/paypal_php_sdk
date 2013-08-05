<?php
/**
 * The PayPal PHP SDK. Include this file in your project.
 *
 * @package PayPal
 */
require dirname(__FILE__) . '/lib/shared/PayPalRequest.php';
require dirname(__FILE__) . '/lib/shared/PayPalResponse.php';
require dirname(__FILE__) . '/lib/PayPalExpressCheckout.php';
require dirname(__FILE__) . '/lib/PayPalClassic.php';

/**
 * Exception class for PayPal PHP SDK.
 *
 * @package PayPal
 */
class PayPalException extends Exception
{
}