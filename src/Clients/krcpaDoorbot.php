<?php
/**
 * KRCPA Dootbot - Ring.Com Api Doorbot by Kavod
 *
 */
  namespace KRCPA\Clients;

  class krcpaDoorbot extends krcpaClient
  {
    public $conf = array();
    protected $client;

    public function __construct($client,$conf = array())
    {
      $this->client = $client;

      $config = array_merge(array(),$conf);

      $this->setVariable('id',$conf['id']);
      $this->setVariable('battery_life',$conf['battery_life']);
    }
  }
 ?>
