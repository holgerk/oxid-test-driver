<?php

// to run these test you need to install a ce shop with demo data at:
//   __DIR__ . '/../../oxideshop_ce/source'
// with an admin user:
//   admin
// and password:
//   admin

require_once __DIR__ . '/../lib/OxidTestDriver.php';

// needs to be called before oxid's bootstrap.php is loaded
OxidTestDriver::configure(__DIR__ . '/../../oxideshop_ce/source');



class OxidTestDriverTest extends PHPUnit_Framework_TestCase {

    public function testGetDetailsPage() {
        $driver = new OxidTestDriver;

        $response = $driver->get('cl=details&anid=dc5ffdf380e15674b56dd562a7cb6aec');
        $this->assertInstanceOf('details', $response->controller);
        $this->assertEquals(
            'dc5ffdf380e15674b56dd562a7cb6aec',
            $response->controller->getProduct()->getId());
        $this->assertEquals(
            'OXID Surf- und Kiteshop | Kuyichi Ledergürtel JEVER | online kaufen',
            trim($response->titleTag));

        $response = $driver->get('cl=details&anid=f4f73033cf5045525644042325355732');
        $this->assertInstanceOf('details', $response->controller);
        $this->assertEquals(
            'f4f73033cf5045525644042325355732',
            $response->controller->getProduct()->getId());
        $this->assertEquals(
            'OXID Surf- und Kiteshop | Transportcontainer THE BARREL | online kaufen',
            trim($response->titleTag));
    }

    public function testLogin() {
        $driver = new OxidTestDriver;
        $response = $driver->post('fnc=login_noredirect&cl=account&lgn_usr=admin&lgn_pwd=admin');
        $this->assertEquals('account', $response->controller->getClassName());
        $this->assertNotEmpty($response->sessionId, 'Session ID is empty!');
        $this->assertEquals('oxdefaultadmin', $response->user->getId());
    }

    public function testAddToBasket() {
        $driver = new OxidTestDriver;

        // add to basket
        $response = $driver->post(array(
            'fnc'  => 'tobasket',
            'aid'  => 'f4f73033cf5045525644042325355732',
            'am'   => '1',
        ));
        $this->assertEquals('start', $response->controller->getClassName());
        $this->assertNotEmpty($response->sessionId, 'Session ID is empty!');
        $this->assertFalse($response->user);
        $this->assertEquals(1, count($response->basketItems));
        $this->assertNotNull($response->redirect);

        // follow redirect
        $response = $driver->get($response->redirect->url);
        $this->assertEquals(1, count($response->basketItems));
        $this->assertEquals(
            'Neuer Artikel wurde in den Warenkorb gelegt',
            $response->xpath('//*[@id="newItemMsg"]')->item(0)->nodeValue
        );
    }

    public function testTiming() {
        $driver = new OxidTestDriver;
        $response = $driver->post(array(
            'fnc'  => 'tobasket',
            'aid'  => 'f4f73033cf5045525644042325355732',
            'am'   => '1',
        ));

        $this->assertGreaterThan(0, $response->time);
    }

    public function testCookiesAreKept() {
        $driver = new OxidTestDriver;

        $response = $driver->post(array(
            'fnc'  => 'tobasket',
            'aid'  => 'f4f73033cf5045525644042325355732',
            'am'   => '1',
        ));

        $this->assertEquals(1, count($response->basketItems));
        $response = $driver->get('cl=details&anid=dc5ffdf380e15674b56dd562a7cb6aec');
        $this->assertEquals(1, count($response->basketItems));
    }

    public function testSeoUrl() {
        $driver = new OxidTestDriver;
        $response = $driver->get('/Angebote/Transportcontainer-THE-BARREL.html?param=42');
        $this->assertInstanceOf('details', $response->controller);
        $this->assertEquals(
            'f4f73033cf5045525644042325355732',
            $response->controller->getProduct()->getId());
        $this->assertRegexp('/Transportcontainer THE BARREL/', $response->titleTag);
        $this->assertEquals('42', $_GET['param']);
        //
    }

    public function testSeoUrlAndPost() {
        $driver = new OxidTestDriver;
        $response = $driver->post(
            '/Angebote/Transportcontainer-THE-BARREL.html?param=42',
            array(
                'fnc'     => 'login_noredirect',
                'lgn_usr' => 'admin',
                'lgn_pwd' => 'admin',
            )
        );
        $this->assertInstanceOf('details', $response->controller);
        $this->assertEquals('42', $_GET['param']);
        $this->assertEquals('admin', $_POST['lgn_usr']);
        $this->assertEquals('oxdefaultadmin', $response->user->getId());
    }



}


