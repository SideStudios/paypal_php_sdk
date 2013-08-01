<?php
/**
 * Base class for the PayPal responses.
 *
 * @package    PayPal
 * @subpackage    PayPalResponse
 */


/**
 * Parses an PayPal Response.
 *
 * @package PayPal
 * @subpackage    PayPalResponse
 */
class PayPalResponse
{

    public $status;
    public $correlationid;
    public $timestamp;
    public $errors = array();
    public $warnings = array();
    public $response; // The response string from PayPal.

}
