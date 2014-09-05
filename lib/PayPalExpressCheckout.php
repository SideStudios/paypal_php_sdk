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
 * @subpackage PayPalExpressCheckout
 */

/**
 * Builds and sends an PayPal Request.
 *
 * @package    PayPal
 * @subpackage PayPalExpressCheckout
 */
class PayPalExpressCheckout extends PayPalRequest {

	const LIVE_LOGIN_URL = 'https://www.paypal.com/cgi-bin/webscr';
	const SANDBOX_LOGIN_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

	/**
	 * Holds all the default name/values that will be posted in the request.
	 * Default values are provided for best practice fields.
	 */
	protected $_post_fields = array(
		"version" => "104",
		);

	/**
	 * Used for sending line items
	 */
	private $_additional_line_items = array();

	/**
	 * Used for paymentrequest fields
	 */
	private $_payment_fields = array();

	/**
	 * Used for shipping option fields
	 */
	private $_shipping_options = array();

	/**
	 * Only used if merchant wants to send custom fields.
	 */
	private $_custom_fields = array();

	/**
	 * Checks to make sure a field is actually in the API before setting.
	 * Set to false to skip this check.
	 */
	public $verify_fields = true;

	/**
	 * A list of all fields in the API.
	 * Used to warn user if they try to set a field not offered in the API.
	 */
	private $_string_fields = array("method","maxamt","returnurl",
		"cancelurl","callback", "callbacktimeout","reqconfirmshipping",
		"noshipping","allownote","addroverride","callbackversion",
		"localecode","pagestyle","hdrimg","payflowcolor","cartbordercolor",
		"logoimg","email","solutiontype","landingpage","channeltype","giropaysuccessurl",
		"giropaycancelurl","banktxnpendingurl","brandname","customerservicenumber","giftmessageenable",
		"giftreceiptenable","giftwrapenable","giftwrapname","giftwrapamount","buyeremailoptinenable",
		"surveyenable","surveyquestion","payerid","returnfmdetails","giftmessage",
		"buyermarketingemail","surveychoiceselected","buttonsource","insuranceoptionselected",
		"shippingoptionisdefault","shippingoptionamount","shippingoptionname","currencycode",
		"offerinsuranceoption","no_shipping_option_details", "token");

	private $_pattern_fields = array(
		"paymentrequest_[0-9]+_(amt|currencycode|itemamt|shippingamt|insuranceamt|shipdiscamt|insuranceoptionoffered|handlingamt|taxamt|desc|custom|invnum|notifyurl|multishipping|notetext|softdescriptor|transactionid|allowedpaymentmethod|paymentaction|paymentrequestid|paymentreason)",
		"paymentrequest_[0-9]+_(shiptoname,shiptostreet,shiptostreet2,shiptostate,shiptozip,shiptocountrycode,shiptophonenum)",
		"paymentrequest_[0-9]+_(name|desc|amt|number|qty|taxamt|itemweightvalue|itemweightunit|itemlengthvalue|itemlengthunit|itemwidthvalue|itemwidthunit|itemheightvalue|itemheightunit|itemurl|itemcategory)[0-9]+",
		"paymentrequest_[0-9]+_(sellerid|sellerusername|sellerregistrationdate)",
		"l_insuranceamount[0-9]+",
		"l_surveychoice[0-9]+",
		"l_shippingoption(amount|isdefault|label|name)[0-9]+",
		"l_taxamt[0-9]+",
	);

	/**
	 * Alternative syntax for setting fields.
	 *
	 * Usage: $sale->method = "GetExpressCheckoutDetails";
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

		$this->_payment_fields = array_merge($this->_payment_fields, compact('shiptoname', 'shiptostreet', 'shiptocity', 'shiptostate', 'shiptozip', 'shiptocountrycode', 'shiptophonenum'));
		$this->addroverride = 1;
	}

	/**
	 * Add a line item.
	 *
	 * @param string $number
	 * @param string $name
	 * @param string $desc
	 * @param string $qty
	 * @param string $amt
	 * @param string $taxamt
	 * @param string $itemurl
	 * @param string $itemcategory
	 */
	public function addLineItem($number, $name, $desc, $qty, $amt, $taxamt, $itemurl = null, $itemcategory = 'Physical')
	{
		$this->_additional_line_items[] = compact('number', 'name', 'desc', 'qty', 'amt', 'taxamt', 'itemurl', 'itemcategory');
	}

	/**
	 * Add order details
	 * @param string $itemamt     Total amount for items
	 * @param string $shippingamt Total shipping amount
	 * @param string $taxamt      Total tax amount
	 * @param string $invnum 	  Invoice number
	 */
	public function addOrder($itemamt = null, $shippingamt = null, $taxamt = null, $invnum = null)
	{
		$this->_payment_fields = array_merge($this->_payment_fields, compact('itemamt', 'shippingamt', 'taxamt', 'invnum'));
	}

	/**
	 * Add shipping option (for callbacks)
	 *
	 * If no shipping options are added, respondToCallback() will set NO_SHIPPING_OPTION_DETAILS
	 *
	 * @param string  $shippingoptionname      Internal/system name for shipping option
	 * @param string  $shippingoptionlabel     Label for shipping option
	 * @param string  $shippingoptionamount    Shipping amount for this option
	 * @param boolean $shippingoptionisdefault Whether or not to set this option as the default
	 * @param string  $taxamt				   Tax amount for this shipping option
	 */
	public function addShippingOption($shippingoptionname, $shippingoptionlabel, $shippingoptionamount, $shippingoptionisdefault = false, $taxamt) {

		// Validate fields
		if (strlen($shippingoptionname) > 50) throw new PayPalException('Shipping option name cannot exceed 50 characters!');
		if (strlen($shippingoptionlabel) > 50) throw new PayPalException('Shipping option label cannot exceed 50 characters!');
		$shippingoptionisdefault = $shippingoptionisdefault ? 'true' : 'false';

		$this->_shipping_options[] = compact('shippingoptionname', 'shippingoptionlabel', 'shippingoptionamount', 'shippingoptionisdefault', 'taxamt');

	}

	/**
	 * Parse callback request
	 * @param  array $request Request parameters
	 * @return PayPalCallback
	 */
	public function callbackRequest($request) {
		return new PayPalCallback($request);
	}

	/**
	 * Create response for callback based on set parameters
	 * @return void
	 */
	public function callbackResponse() {

		$this->method = 'CallbackResponse';

		if (!$this->_shipping_options) $this->no_shipping_option_details = 1;
		if (empty($this->_post_fields['currencycode'])) $this->currencycode = 'USD';
		$this->unsetField('version');

		$this->_setPostString();
		return $this->_post_string;

	}

	/**
	 * Do an express checkout
	 *
	 * Should be called when the order is being confirmed and completes the PayPal checkout
	 *
	 * @param string $token     Token returned from GetExpressCheckout
	 * @param string $payerid   Payer ID return from GetExpressCheckout
	 * @return PayPalResponse   Response with billing agreement ID
	 */
	public function doECP($token = null, $payerid = null) {

		($token ? $this->token = $token : null);
		($payerid ? $this->payerid = $payerid : null);

		$this->method = 'DoExpressCheckoutPayment';
		return $this->_sendRequest();
	}

	/**
	 * Get express checkout details
	 *
	 * Should be called when a user returns from PayPal to your website
	 *
	 * @param  string $token    Token returned from PayPal
	 * @return PayPalResponse   Response with user and shipping information
	 */
	public function getECD($token = null) {

		($token ? $this->token = $token : null);

		$this->method = 'GetExpressCheckoutDetails';
		return $this->_sendRequest();

	}

	/**
	 * Redirect to login page for buyer based on token
	 * @param  string $token
	 * @return void
	 */
	public function redirect($token) {

		if ($this->_sandbox) $url = self::SANDBOX_LOGIN_URL;
		else $url = self::LIVE_LOGIN_URL;

		$url .= '?cmd=_express-checkout&token=' . $token;

		header('Location:' . $url);
		die();

	}

	/**
	 * Set an express checkout
	 *
	 * Should be called from the cart or from the billing step in checkout
	 *
	 * @param string $amount    Amount for transaction
	 * @param string $returnurl Return URL after buyer is done on PayPal
	 * @param string $cancelurl Cancel URL if buyer chooses to return
	 * @param string $callback  Callback URL for PayPal to retrieve shipping data
	 * @param string $maxamt    Maximum expected amount for this order (only if callback is specified)
	 * @return PayPalResponse Response with token to redirect user to PayPal
	 */
	public function setEC($amt = null, $returnurl = null, $cancelurl = null, $callback = null, $maxamt = null) {

		($amt ? $this->paymentrequest_0_amt = $amt : null);
		($returnurl ? $this->returnurl = $returnurl : null);
		($cancelurl ? $this->cancelurl = $cancelurl : null);

		// Set call back and default (invalid) shipping method
		// The shipping method will be overridden once PayPal gets results from the callback URL
		if ($callback) {
			$this->callback = $callback;
			$this->callbackversion = 104;
			$this->callbacktimeout = 6;
			$this->l_shippingoptionisdefault0 = true;
			$this->l_shippingoptionname0 = 'Unable to ship';
			$this->l_shippingoptionamount0 = '0';
			($maxamt ? $this->maxamt = $maxamt : null);
		}

		$this->method = 'SetExpressCheckout';
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
		if ($this->verify_fields) {
			if (in_array($name, $this->_string_fields)) {
				$this->_post_fields[$name] = $value;
			} else {
				foreach($this->_pattern_fields as $pattern) {
					if (preg_match("/" . $pattern . "/", $name)) {
						$this->_post_fields[$name] = $value;
						return;
					}
				}
				throw new PayPalException("Error: no field $name exists in the PayPal API.");
			}
		} else {
			$this->_post_fields[$name] = $value;
		}
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
	 * Set an individual payment field name/value pair.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setPaymentField($name, $value)
	{
		if ($this->verify_fields) {
			if (in_array($name, $this->_string_fields)) {
				$this->_payment_fields[$name] = $value;
			} else {
				foreach($this->_pattern_fields as $pattern) {
					if (preg_match("/" . $pattern . "/", $name)) {
						$this->_payment_fields[$name] = $value;
						return;
					}
				}
				throw new PayPalException("Error: no field $name exists in the PayPal API.");
			}
		} else {
			$this->_payment_fields[$name] = $value;
		}
	}

	/**
	 * Quickly set multiple payment fields.
	 *
	 * @param array $fields Takes an array or object.
	 */
	public function setPaymentFields($fields)
	{
		$array = (array)$fields;
		foreach ($array as $key => $value) {
			$this->setPaymentField($key, $value);
		}
	}

	/**
	 * Unset a payment field.
	 *
	 * @param string $name Field to unset.
	 */
	public function unsetPaymentField($name)
	{
		unset($this->_payment_fields[$name]);
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
	 * @return PayPalExpressCheckout_Response
	 */
	protected function _handleResponse($response)
	{
		return new PayPalExpressCheckout_Response($response);
	}

	/**
	 * Converts the post_fields array into a string suitable for posting.
	 */
	protected function _setPostString()
	{

		if ($this->_post_fields['method'] != 'CallbackResponse') {
			$this->_post_fields['USER'] = $this->_api_username;
			$this->_post_fields['PWD'] = $this->_api_password;
			$this->_post_fields['SIGNATURE'] = $this->_api_signature;
		}

		$this->_post_string = "";
		foreach($this->_post_fields as $key => $value) {
			$this->_post_string .= strtoupper($key) . "=" . urlencode($value) . "&";
		}
		// Add line items
		foreach($this->_additional_line_items as $index => $line_item) {
			foreach($line_item as $key => $value) {
				$this->_post_string .= "L_PAYMENTREQUEST_0_" . strtoupper($key) . $index . "=" . urlencode($value) . "&";
			}
		}
		// Add payment request fields
		foreach($this->_payment_fields as $key => $value) {
			$this->_post_string .= "PAYMENTREQUEST_0_" . strtoupper($key) . "=" . urlencode($value) . "&";
		}
		// Add shipping methods
		foreach($this->_shipping_options as $index => $option) {
			foreach($option as $key => $value) {
				$this->_post_string .= "L_" . strtoupper($key) . $index . "=" . urlencode($value) . "&";
			}
		}
		// Add custom fields
		foreach($this->_custom_fields as $key => $value) {
			$this->_post_string .= "$key=" . urlencode($value) . "&";
		}
		$this->_post_string = rtrim($this->_post_string, "& ");
	}

}

/**
 * Parses an PayPal Express Checkout Response
 *
 * @package    PayPal
 * @subpackage PayPalExpressCheckout
 */
class PayPalExpressCheckout_Response extends PayPalResponse {

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

/**
 * Parses a PayPal Callback
 *
 * @package    PayPal
 * @subpackage PayPalCallback
 */
class PayPalCallback extends PayPalResponse {

	public $_response_array = array(); // An array with the original response.

	/**
	 * Constructor. Parses the PayPal response string.
	 *
	 * @param array $request      The callback request parameters from the PayPal server.
	 */
	public function __construct($request) {

		$this->_response_array = $request;

		foreach($request as $key => $value) {
			$this->{strtolower($key)} = $value;
		}

	}

}
