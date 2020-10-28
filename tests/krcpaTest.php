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

    public function instance($config = array()): KRCPA\Clients\krcpaClient
    {
      $client = clone self::$ref_client;
      return $client;
    }

    public function testInstance(): void
    {
        $client = $this::instance(self::$conf);
        $this->assertInstanceOf(
            \KRCPA\Clients\krcpaClient::class,
            $client
        );
    }

    public function testAuth(): void
    {
        $client = $this::instance(self::$conf);
        $this->assertNotFalse($client->auth_refresh());
        $this->assertNotFalse($client->getVariable('token',false));
    }

    public function testGetDevices(): void
    {
        $client = $this::instance(self::$conf);
        $client->auth_refresh();
        $devices = $client->getDevices();
        $this->assertIsArray($devices);
        foreach($devices as $device)
        {
          $this->assertInstanceOf(KRCPA\Clients\krcpaDoorbot::class,$device);
          $this->assertIsNumeric($device->getVariable('battery_life'));
        }
    }

    public function testGetHistory(): void
    {
        $client = $this::instance(self::$conf);
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
        $client = $this::instance(self::$conf);
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
      $client = $this::instance(self::$conf);
      $client->auth_refresh();
      $this->assertStringContainsString('.',$client->getVersion());
    }

    public function testAutoReconnect():void
    {
      //$conf = array_merge(array(),self::$conf); # Copy by val
      $client = $this::instance(self::$conf);
      $client->auth_refresh();
      $client->setVariable('token','Bearer Niouf');
      $devices = $client->getDevices();
      foreach($devices as $device)
      {
        $this->assertInstanceOf(KRCPA\Clients\krcpaDoorbot::class,$device);
        $this->assertIsNumeric($device->getVariable('battery_life'));
      }
    }

    public function testAvoidInfiniteLoop():void
    {
      //$conf = array_merge(array(),self::$conf); # Copy by val
      $client = $this::instance(self::$conf);
      $client->auth_refresh();
      $client->setVariable('token','Bearer Niouf');
      $client->setVariable('refresh_token','Niorf');
      $devices = $client->getDevices();
      $this->assertIsArray($devices);
      $this->assertcount(0,$devices);
    }

}
?>
