<?php


require_once __DIR__ . '/../lib/TestDriver.php';

// needs to be called before oxid's bootstrap.php is loaded
TestDriver::configure(__DIR__ . '/../../oxideshop_ce/source');



class TestDriverTest extends PHPUnit_Framework_TestCase {

    public function testGetDetailsPage() {
        $driver = new TestDriver;

        $result = $driver->get('cl=details&anid=dc5ffdf380e15674b56dd562a7cb6aec');
        $this->assertInstanceOf('details', $result->controller);
        $this->assertEquals(
            'dc5ffdf380e15674b56dd562a7cb6aec',
            $result->controller->getProduct()->getId());
        $this->assertEquals(
            'OXID Surf- und Kiteshop | Kuyichi Ledergürtel JEVER | online kaufen',
            trim($result->titleTag));

        $result = $driver->get('cl=details&anid=f4f73033cf5045525644042325355732');
        $this->assertInstanceOf('details', $result->controller);
        $this->assertEquals(
            'f4f73033cf5045525644042325355732',
            $result->controller->getProduct()->getId());
        $this->assertEquals(
            'OXID Surf- und Kiteshop | Transportcontainer THE BARREL | online kaufen',
            trim($result->titleTag));
    }

    public function testResetState() {
        $this->assertFalse(isset($_GET['cl']));
        $driver = new TestDriver;
        $result = $driver->get('cl=details&anid=dc5ffdf380e15674b56dd562a7cb6aec');
        $this->assertFalse(isset($_GET['cl']));
    }

    public function testLogin() {
        $driver = new TestDriver;
        $result = $driver->post('fnc=login_noredirect&cl=account&lgn_usr=admin&lgn_pwd=admin');
        $this->assertEquals('account', $result->controller->getClassName());
        $this->assertNotEmpty($result->sessionId, 'Session ID is empty!');
        $this->assertEquals('oxdefaultadmin', $result->user->getId());
    }

}


