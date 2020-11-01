<?php
/**
 * KRCPA Dootbot - Ring.Com Api Doorbot by Kavod
 *
 */
  namespace KRCPA\Clients;

  class krcpaDoorbot extends krcpaDevice
  {
    public $conf = array();
    protected $client;

    public function __construct($client,$conf = array())
    {
      parent::__construct($client,$conf);

      // Battery
      $this->conf['battery'] = true;
      $this->setVariable('battery_life',$conf['battery_life']);

      // Ring
      $this->conf['features']['ring'] = true;

      // Volume
      $this->conf['features']['volume'] = true;
      $this->setVariable('volume',$conf['settings']['doorbell_volume']);
    }
  }
 ?>
