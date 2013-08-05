<?php
/**
 * Sends requests to PayPal
 *
 * @package    PayPalSDK
 * @subpackage PayPalRequest
 */
abstract class PayPalRequest {

	const LIVE_URL = 'https://api-3t.paypal.com/nvp';
	const SANDBOX_URL = 'https://api-3t.sandbox.paypal.com/nvp';
	
	protected $_api_username;
	protected $_api_password;
	protected $_api_signature;
	protected $_post_string; 
	public $VERIFY_PEER = false; // Set to false if getting connection errors.
	protected $_sandbox = true;
	protected $_log_file = false;
	
	/**
	 * Set the _post_string
	 */
	abstract protected function _setPostString();
	
	/**
	 * Handle the response string
	 */
	abstract protected function _handleResponse($string);
	
	/**
	 * Get the post url. We need this because until 5.3 you
	 * you could not access child constants in a parent class.
	 */
	abstract protected function _getPostUrl();
	
	/**
	 * Constructor.
	 *
	 * @param string $api_username      The merchant's API Login ID.
	 * @param string $api_password      The merchant's API Password
	 * @param string $api_signature     The merchant's API Signature
	 */
	public function __construct($api_username = false, $api_password = false, $api_signature = false)
	{
		$this->_api_username = ($api_username ? $api_username : (defined('PAYPAL_API_USERNAME') ? PAYPAL_API_USERNAME : ""));
		$this->_api_password = ($api_password ? $api_password : (defined('PAYPAL_API_PASSWORD') ? PAYPAL_API_PASSWORD : ""));
		$this->_api_signature = ($api_signature ? $api_signature : (defined('PAYPAL_API_SIGNATURE') ? PAYPAL_API_SIGNATURE : ""));
		$this->_sandbox = (defined('PAYPAL_SANDBOX') ? PAYPAL_SANDBOX : false);
		$this->_log_file = (defined('PAYPAL_LOG_FILE') ? PAYPAL_LOG_FILE : false);
	}

	/**
	 * Alter the gateway url.
	 *
	 * @param bool $bool Use the Sandbox.
	 */
	public function setSandbox($bool)
	{
		$this->_sandbox = $bool;
	}
	
	/**
	 * Set a log file.
	 *
	 * @param string $filepath Path to log file.
	 */
	public function setLogFile($filepath)
	{
		$this->_log_file = $filepath;
	}
	
	/**
	 * Return the post string.
	 *
	 * @return string
	 */
	public function getPostString()
	{
		return $this->_post_string;
	}
	
	/**
	 * Posts the request to PayPal & returns response.
	 *
	 * @return PayPalResponse The response.
	 */
	protected function _sendRequest()
	{
		$this->_setPostString();
		$post_url = $this->_getPostUrl();
		$curl_request = curl_init($post_url);
		curl_setopt($curl_request, CURLOPT_POSTFIELDS, $this->_post_string);
		curl_setopt($curl_request, CURLOPT_HEADER, 0);
		curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
		curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, 2);
		if ($this->VERIFY_PEER) {
			curl_setopt($curl_request, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . '/ssl/cert.pem');
		} else {
			curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		if (preg_match('/xml/',$post_url)) {
			curl_setopt($curl_request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		}
		
		$response = curl_exec($curl_request);
		
		if ($this->_log_file) {
		
			if ($curl_error = curl_error($curl_request)) {
				file_put_contents($this->_log_file, "----CURL ERROR----\n$curl_error\n\n", FILE_APPEND);
			}
			
			file_put_contents($this->_log_file, "----Response----\n$response\n\n", FILE_APPEND);
		}
		curl_close($curl_request);
		
		return $this->_handleResponse($response);
	}

}