<?php

require_once __DIR__ . '/../lib/OxidTestDriver.php';

// needs to be called before oxid's bootstrap.php is loaded
OxidTestDriver::configure(__DIR__ . '/../../oxideshop_ce/source');



class ExampleTest extends PHPUnit_Framework_TestCase {

    public function testCheckout() {
        $driver = new OxidTestDriver;

        // add article to basket
        $response = $driver->post(array(
            'fnc' => 'tobasket',
            'aid' => 'dc5ffdf380e15674b56dd562a7cb6aec',
            'am'  => '1'));
        $this->assertEquals(1, count($response->basketItems));

        // login
        $response = $driver->post(array(
            'cl'      => 'user',
            'fnc'     => 'login_noredirect',
            'lgn_usr' => 'admin',
            'lgn_pwd' => 'admin'));
        $this->assertEquals('admin', $response->user->oxuser__oxusername->value);

        // select payment
        $response = $driver->post(array(
            'cl'        => 'payment',
            'fnc'       => 'validatepayment',
            'paymentid' => 'oxidpayadvance'));
        $this->assertRegexp('/cl=order/', $response->redirect->url);

        // order overview (follow redirect)
        $response = $driver->get($response->redirect->url);
        $this->assertEquals('order', $response->controller->getClassName());

        // execute order
        $response = $driver->post(array(
            'cl'     => 'order',
            'fnc'    => 'execute',
            'stoken' => oxRegistry::getSession()->getSessionChallengeToken(),
            'sDeliveryAddressMD5' => $response->controller->getDeliveryAddressMD5(),
        ));
        $this->assertRegexp('/cl=thankyou/', $response->redirect->url);
    }



}


