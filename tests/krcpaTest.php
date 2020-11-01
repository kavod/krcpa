<?php

use PHPUnit\Framework\TestCase;

require_once (__DIR__.'/../src/autoload.php');

final class krcpaTest extends TestCase
{
    protected static $conf;
    protected static $ref_client;

    public static function setUpBeforeClass(): void
    {
      require(__DIR__.'/../Examples/Config.php');
      self::assertRegExp('/.+/',$username);
      self::$conf = array(
        "username" => $username,
        "password" => $password,
        "auth_code" => $auth_code,
        "refresh_token" => $refresh_token
      );
      self::$ref_client = new KRCPA\Clients\krcpaClient(self::$conf);
      // $dev = new kavod\Clients\krcpaDoorbot(self::$ref_client,self::$conf);
    }

    public function instance(): KRCPA\Clients\krcpaClient
    {
      $client = clone self::$ref_client;
      return $client;
    }

    public function testInstance(): void
    {
        $client = $this::instance();
        $this->assertInstanceOf(
            \KRCPA\Clients\krcpaClient::class,
            $client
        );
    }

    public function testAuth(): void
    {
        $client = $this::instance();
        $this->assertNotFalse($client->auth_refresh());
        $this->assertNotFalse($client->getVariable('token',false));
    }

    public function testGetDevices(): void
    {
        $client = $this::instance();
        $client->auth_refresh();
        $devices = $client->getDevices();
        $this->assertIsArray($devices);
        foreach($devices['doorbots'] as $device)
        {
          $this->assertInstanceOf(KRCPA\Clients\krcpaDoorbot::class,$device);
          $this->assertIsNumeric($device->getVariable('battery_life'));
        }
        foreach($devices['chimes'] as $device)
        {
          $this->assertInstanceOf(KRCPA\Clients\krcpaChime::class,$device);
          $this->assertIsNumeric($device->getVariable('volume'));
        }
    }

    public function testGetHistory(): void
    {
        $client = $this::instance();
        $client->auth_refresh();
        $history = $client->getHistory();
        $this->assertIsArray($history);
        foreach($history as $event)
        {
          $this->assertInstanceOf(KRCPA\Clients\krcpaHistory::class,$event);
          $this->assertIsNumeric($event->getVariable('id',''));
        }
    }

    public function testGetActiveDings(): void
    {
        $client = $this::instance();
        $client->auth_refresh();
        $dings = $client->getActiveDings();
        $this->assertIsArray($dings);
        foreach($dings as $ding)
        {
          $this->assertInstanceOf(KRCPA\Clients\krcpaDing::class,$ding);
          $this->assertIsNumeric($ding->getVariable('id',''));
        }
    }

    public function testGetVersion():void
    {
      $client = $this::instance();
      $client->auth_refresh();
      $this->assertStringContainsString('.',$client->getVersion());
    }

    public function testAutoReconnect():void
    {
      //$conf = array_merge(array(),self::$conf); # Copy by val
      $client = $this::instance();
      $client->auth_refresh();
      $client->setVariable('token','Bearer Niouf');
      $devices = $client->getDevices();
      $this->assertArrayHasKey('doorbots',$devices);
      foreach($devices['doorbots'] as $device)
      {
        $this->assertInstanceOf(KRCPA\Clients\krcpaDoorbot::class,$device);
        $this->assertIsNumeric($device->getVariable('battery_life'));
      }
    }

    public function testAvoidInfiniteLoop1():void
    {
      $this->expectException(KRCPA\Exceptions\krcpaClassException::class);
      $this->expectExceptionCode(3);

      $client = new KRCPA\Clients\krcpaClient();
      $client->auth_refresh();
      $client->setVariable('token','Bearer Niouf');
      $client->setVariable('refresh_token','Niorf');
      $devices = $client->getDevices();
      $this->assertIsArray($devices);
      $this->assertArrayHasKey('doorbots',$devices);
      $this->assertcount(0,$devices['doorbots']);
    }

    public function testAvoidInfiniteLoop2():void
    {
      $this->expectException(KRCPA\Exceptions\krcpaClassException::class);
      $this->expectExceptionCode(5);

      $client = new KRCPA\Clients\krcpaClient();
      $client->setVariable('token','Bearer Niouf');
      $client->setVariable('refresh_token','Niorf');
      $devices = $client->getDevices();
      $this->assertIsArray($devices);
      $this->assertArrayHasKey('doorbots',$devices);
      $this->assertcount(0,$devices['doorbots']);
    }

    public function testIsAuth(): void
    {
      $client = new KRCPA\Clients\krcpaClient();
      $this->assertFalse($client->isAuth());
      try {
        $client->auth_refresh('niouf');
      } catch (\Exception $e)
      {

      }
      $this->assertFalse($client->isAuth());
      $client->auth_refresh(self::$conf['refresh_token']);
      $this->assertTrue($client->isAuth());
    }

    public function testIs_featured(): void
    {
      $client = new KRCPA\Clients\krcpaClient();
      $client->auth_refresh(self::$conf['refresh_token']);
      $devices = $client->getDevices();
      $this->assertArrayHasKey('doorbots',$devices);
      foreach($devices['doorbots'] as $device)
      {
        $this->assertTrue($device->is_featured('motions_enabled'));
        $this->assertFalse($device->is_featured('show_offline_motion_events'));
        $this->assertFalse($device->is_featured('niouf'));
      }
    }

    public function testGetDeviceById(): void
    {
      $client = new KRCPA\Clients\krcpaClient();
      $client->auth_refresh(self::$conf['refresh_token']);
      $devices = $client->getDevices();
      $this->assertArrayHasKey('doorbots',$devices);
      foreach($devices['doorbots'] as $device)
      {
        $this->assertEquals($device,$client->getDeviceById($device->getVariable('id')));
        $this->assertNull($client->getDeviceById('niouf'));
      }
    }

    public function testGetDoNotDisturb(): void
    {
      $time = 300;
      $tol = 1;

      $client = new KRCPA\Clients\krcpaClient();
      $client->auth_refresh(self::$conf['refresh_token']);
      $devices = $client->getDevices();
      foreach($devices['chimes'] as $device)
      {
        $this->assertInstanceOf(KRCPA\Clients\krcpaChime::class,$device);
        $time1 = $device->setDoNotDisturb($time);
        $this->assertIsNumeric($time1);
        $this->assertEquals($time1,$time);
        $time2 = $device->getDoNotDisturb();
        $this->assertIsNumeric($time2);
        $this->assertLessThanOrEqual($time2,$time);
        $this->assertGreaterThanOrEqual($time2-$tol,$time);
        $time3 = $device->setDoNotDisturb(0);
        $this->assertEquals($time3,0);
        $time4 = $device->getDoNotDisturb();
        $this->assertEquals($time4,0);
      }
    }
}
?>
