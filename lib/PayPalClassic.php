<?php
/**
 * Easily interact with the PayPal API.
 *
 * Note: To send requests to the live gateway, either define this:
 * define("PAYPAL_SANDBOX", false);
 *   -- OR --
 * $sale = new PayPal;
 * $sale->setSandbox(false);
 *
 * @package    PayPal
 * @subpackage PayPalClassic
 */

/**
 * Builds and sends an PayPal Request.
 *
 * @package    PayPal
 * @subpackage PayPalClassic
 */
class PayPalClassic extends PayPalRequest {

    /**
     * Holds all the default name/values that will be posted in the request.
     * Default values are provided for best practice fields.
     */
    protected $_post_fields = array(
        "version" => "104",
        );

    /**
     * Checks to make sure a field is actually in the API before setting.
     * Set to false to skip this check.
     */
    public $verify_fields = true;

    /**
     * A list of all fields in the API.
     * Used to warn user if they try to set a field not offered in the API.
     */
    private $_string_fields = array("method","authorizationid","note","msgsubid","transactionid",
        "amt","transactionentity","currencycode","itemamt","shippingamt","handlingamt","taxamt",
        "insuranceamt","shipdiscamt","desc","custom","ipaddress","shiptoname","shiptostreet","shiptostreet2",
        "shiptocity","shiptostate","shiptozip","shiptocountry","shiptophonenum","completetype","invnum",
        "softdescriptor","storeid","terminalid","payerid","invoiceid","refundtype","retryuntil","refundsource",
        "merchantstoredetails","refundadvice","refunditemdetails");

    /**
     * Alternative syntax for setting fields.
     *
     * Usage: $sale->method = "DoVoid";
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value)
    {
        $this->setField($name, $value);
    }

    /**
     * Add a shipping address
     * @param string $shiptoname        Name
     * @param string $shiptostreet      Street address
     * @param string $shiptocity        City
     * @param string $shiptostate       State Abbrevation
     * @param string $shiptozip         ZIP
     * @param string $shiptocountrycode Country code (E.G. US)
     * @param string $shiptophonenum    Optional phone number
     */
    public function addShippingAddress($shiptoname, $shiptostreet, $shiptocity, $shiptostate, $shiptozip, $shiptocountrycode, $shiptophonenum = null)
    {
        $address = func_get_args();

        // Validate fields
        if (strlen($shiptoname) > 32) throw new PayPalException('Shipping address name cannot exceed 32 characters!');
        if (strlen($shiptostreet) > 200) throw new PayPalException('Shipping street address cannot exceed 200 characters!');
        else if (strlen($shiptostreet) > 100) {
            $words = explode(" ", $shiptostreet);
            $address['shiptostreet'] = "";
            while(strlen($address['shiptostreet']) < 100 && $words) $address['shiptostreet'] += array_shift($words) . " ";
            if ($words) $address['shiptostreet2'] = implode(" ", $words);
        }
        if (strlen($shiptocity) > 40) throw new PayPalException('Shipping address city cannot exceed 40 characters!');
        if (strlen($shiptozip) > 20) throw new PayPalException('Shipping address postal code/ZIP cannot exceed 20 characters!');
        if (strlen($shiptocountrycode) > 2) throw new PayPalException('Shipping address country must be a 2 character code!');
        if ($shiptophonenum && strlen($shiptophonenum) > 20) throw new PayPalException('Shipping address phone number cannot exceed 20 characters!');
        else if (is_null($shiptophonenum)) unset($address['shiptophonenum']);

        $this->_post_fields = array_merge($this->_post_fields, compact('shiptoname', 'shiptostreet', 'shiptocity', 'shiptostate', 'shiptozip', 'shiptocountrycode', 'shiptophonenum'));

    }

    /**
     * Add order details
     * @param string $itemamt     Total amount for items
     * @param string $shippingamt Total shipping amount
     * @param string $taxamt      Total tax amount
     */
    public function addOrder($itemamt = null, $shippingamt = null, $taxamt = null)
    {
        $this->_post_fields = array_merge($this->_payment_fields, compact('itemamt', 'shippingamt', 'taxamt'));
    }

    /**
     * Authorize a payment
     *
     * Should be called to authorize an existing transaction
     *
     * @param string $transactionid Existing payment transaction ID
     * @param string $amt           Amount to authorize
     * @return PayPalResponse       Response with transaction ID
     */
    public function authorize($transactionid = null, $amt = null) {

        ($transactionid ? $this->transactionid = $transactionid : null);
        ($amt ? $this->amt = $amt : null);

        $this->method = 'DoAuthorization';
        return $this->_sendRequest();
    }

    /**
     * Capture a payment
     *
     * Should be called to capture an existing transaction
     *
     * @param string $authorizationid   Existing authorized transaction ID
     * @param string $amt               Amount to authorize
     * @param string $completeType      Whether or not this is the last capture [Complete|NotComplete]
     * @return PayPalResponse           Response with transaction ID
     */
    public function capture($authorizationid = null, $amt = null, $completetype = 'Complete') {

        ($authorizationid ? $this->authorizationid = $authorizationid : null);
        ($amt ? $this->amt = $amt : null);
        ($completetype ? $this->completetype = $completetype : null);

        $this->method = 'DoCapture';
        return $this->_sendRequest();
    }

    /**
     * Reauthorize a payment
     *
     * Should be called to reauthorize an existing transaction
     *
     * @param string $authorizationid Existing payment transaction ID
     * @param string $amt           Amount to reauthorize
     * @return PayPalResponse       Response with transaction ID
     */
    public function reauthorize($authorizationid = null, $amt = null) {

        ($authorizationid ? $this->authorizationid = $authorizationid : null);
        ($amt ? $this->amt = $amt : null);

        $this->method = 'DoReauthorization';
        return $this->_sendRequest();
    }

    /**
     * Refund a payment
     *
     * Should be called to refund a captured transaction
     *
     * @param string $authorizationid   Existing authorized transaction ID
     * @param string $refundType        Whether or not this is a full or partial refund [Full|Partial|ExternalDispute|Other]
     * @param string $amt               Amount to refund (required only if $refundType set to 'Partial')
     * @return PayPalResponse           Response with refund transaction ID
     */
    public function refund($transactionid = null, $refundtype = 'Full', $amt = null) {

        ($transactionid ? $this->transactionid = $transactionid : null);
        ($refundtype ? $this->refundtype = $refundtype : null);
        ($amt ? $this->amt = $amt : null);

        $this->method = 'RefundTransaction';
        return $this->_sendRequest();
    }

    /**
     * Set an individual name/value pair.
     *
     * @param string $name
     * @param string $value
     */
    public function setField($name, $value)
    {
        if ($this->verify_fields && !in_array($name, $this->_string_fields)) throw new PayPalException("Error: no field $name exists in the PayPal API.");
        $this->_post_fields[$name] = $value;

    }

    /**
     * Quickly set multiple fields.
     *
     * @param array $fields Takes an array or object.
     */
    public function setFields($fields)
    {
        $array = (array)$fields;
        foreach ($array as $key => $value) {
            $this->setField($key, $value);
        }
    }

    /**
     * Unset a field.
     *
     * @param string $name Field to unset.
     */
    public function unsetField($name)
    {
        unset($this->_post_fields[$name]);
    }

     /**
     * Void a payment
     *
     * Should be called to void an authorized transaction
     *
     * @param string $authorizationid   Existing authorized transaction ID
     * @return PayPalResponse           Response
     */
    public function void($authorizationid = null) {

        ($authorizationid ? $this->authorizationid = $authorizationid : null);

        $this->method = 'DoVoid';
        return $this->_sendRequest();
    }

    /**
     * @return string
     */
    protected function _getPostUrl()
    {
        return ($this->_sandbox ? self::SANDBOX_URL : self::LIVE_URL);
    }

    /**
     *
     *
     * @param string $response
     *
     * @return PayPalClassic_Response
     */
    protected function _handleResponse($response)
    {
        return new PayPalClassic_Response($response);
    }

    /**
     * Converts the post_fields array into a string suitable for posting.
     */
    protected function _setPostString()
    {
        $this->_post_fields['USER'] = $this->_api_username;
        $this->_post_fields['PWD'] = $this->_api_password;
        $this->_post_fields['SIGNATURE'] = $this->_api_signature;
        $this->_post_string = "";
        foreach($this->_post_fields as $key => $value) {
            $this->_post_string .= strtoupper($key) . "=" . urlencode($value) . "&";
        }
        $this->_post_string = rtrim($this->_post_string, "& ");
    }

}

/**
 * Parses an PayPal Classic Response
 *
 * @package    PayPal
 * @subpackage PayPalClassic
 */
class PayPalClassic_Response extends PayPalResponse
{

    public $_response_array = array(); // An array with the split response.

    /**
     * Constructor. Parses the PayPal response string.
     *
     * @param string $response      The response from the PayPal server.
     */
    public function __construct($response)
    {
        if ($response) {

            // Split Array
            $this->response = $response;
            parse_str($response, $this->_response_array);

            // Set response fields
            foreach($this->_response_array as $key => $value) {
                $key = strtolower($key);
                if ($key == 'ack') $this->status = $value;
                else if (preg_match('/l_(errorcode|shortmessage|longmessage)([0-9]+)/', $key, $matches)) {
                    if ($this->_response_array['L_SEVERITYCODE' . $matches[2]] == 'Error') $this->errors[$matches[2]][$matches[1]] = $value;
                    else $this->warnings[$matches[2]][$matches[1]] = $value;
                }
                else $this->{$key} = $value;
            }

        } else {
            $this->status = 'Failure';
            $this->errors[] = array('errorcode' => 0, 'shortmessage' => 'Error connecting to PayPal', 'longmessage' => 'Error connecting to PayPal');
        }
    }

}
