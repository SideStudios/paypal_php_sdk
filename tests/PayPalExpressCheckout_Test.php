<?php

require_once 'PayPal_Test_Config.php';

class PayPalExpressCheckout_Sandbox_Test extends PHPUnit_Framework_TestCase
{

     public function testDoExpressCheckoutPayment() {

        $sale = new PayPalExpressCheckout;

        $response = $sale->doECP("test", "test");

        $this->assertEquals($response->status, 'Failure');
        $this->assertEquals($response->errors[0]['shortmessage'], 'Invalid token');

    }

    public function testGetExpressCheckoutDetails() {

        $sale = new PayPalExpressCheckout;

        $response = $sale->getECD("test");

        $this->assertEquals($response->status, 'Failure');
        $this->assertEquals($response->errors[0]['shortmessage'], 'Invalid token');

    }

    public function testSetExpressCheckoutWithCallback() {
        $sale = new PayPalExpressCheckout;

        $sale->addLineItem("SKU123", "Golf club", "Cool club", 2, "10.00", "0.50", "http://example.com/club/");
        $sale->addLineItem("SKU345", "Wilson Balls", "Tennis Balls", 1, "4.00", "0.50", "http://example.com/balls/");
        $sale->addOrder('24.00', null, '1.50');

        $response = $sale->setEC("25.50", "http://example.com/checkout/review/", "http://example.com/cart/", "http://example.com/callback/", "35.50");

        $this->assertEquals($response->status, 'Success');
    }
    
    public function testSetExpressCheckoutWithoutCallback() {
        $sale = new PayPalExpressCheckout;
        $response = $sale->setEC("22.22", "http://example.com/checkout/review/", "http://example.com/cart/");
        $this->assertEquals($response->status, 'Success');
    }
    
    public function testPayPalResponseFields() {
        
        $sale = new PayPalExpressCheckout;
        $response = $sale->setEC("22.22", "http://example.com/checkout/review/", "http://example.com/cart/");
               
        $this->assertEquals($response->status, 'Success');
        $this->assertGreaterThan(1, strlen($response->token));
        $this->assertGreaterThan(1, strlen($response->timestamp));
        $this->assertGreaterThan(1, strlen($response->correlationid));
        $this->assertEquals(104, $response->version);
        $this->assertGreaterThan(1, strlen($response->build));
        
    }

    public function testPayPalCallbackRequest() {

        $request = array(
            'METHOD' => 'CallbackRequest',
            'CALLBACKVERSION' => 104,
            'TOKEN' => 'test',
            'LOCALECODE' => 'en_US',
            'SHIPTOSTREET' => '1 Main St',
            'SHIPTOCITY' => 'San Jose',
            'SHIPTOSTATE' => 'CA',
            'SHIPTOCOUNTRY' => 'US',
            'SHIPTOZIP' => '95131',
            'SHIPTOSTREET2' => '',
        );

        $sale = new PayPalExpressCheckout;
        $request = $sale->callbackRequest($request);

        $this->assertInstanceOf('PayPalCallback', $request);
        $this->assertEquals($request->token, 'test');

    }

    public function testPayPalCallbackResponseWithoutShippingOptions() {

        $sale = new PayPalExpressCheckout;
        $response = $sale->callbackResponse();

        $this->assertContains('METHOD=CallbackResponse', $response);
        $this->assertContains('NO_SHIPPING_OPTION_DETAILS=1', $response);
        $this->assertNotContains('L_SHIPPINGOPTIONNAME', $response);

    }

    public function testPayPalCallbackResponseWithShippingOptions() {

        $sale = new PayPalExpressCheckout;

        $sale->addShippingOption('GND', 'Fed-Ex Ground', '9.95');
        $sale->addShippingOption('AIR', 'Fed-Ex Air', '23.95');

        $response = $sale->callbackResponse();

        $this->assertContains('CURRENCYCODE=USD', $response);
        $this->assertContains('METHOD=CallbackResponse', $response);
        $this->assertContains('L_SHIPPINGOPTIONNAME0=GND', $response);
        $this->assertContains('L_SHIPPINGOPTIONNAME1=AIR', $response);
        $this->assertNotContains('NO_SHIPPING_OPTION_DETAILS', $response);

    }

}