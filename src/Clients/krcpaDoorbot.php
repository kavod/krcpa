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
      $this->setVariable('battery_life',$conf['battery_life']);
    }
  }
 ?>
