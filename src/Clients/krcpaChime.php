<?php
/**
 * KRCPA Dootbot - Ring.Com Api Doorbot by Kavod
 *
 */
  namespace KRCPA\Clients;

  class krcpaChime extends krcpaDevice
  {
    public $conf = array();
    protected $client;

    public function __construct($client,$conf = array())
    {
      parent::__construct($client,$conf);

      // Battery
      $this->conf['features']['battery'] = false;

      // Ring
      $this->conf['features']['ring'] = true;

      // Volume
      $this->conf['features']['volume'] = true;
      $this->setVariable('volume',$conf['settings']['volume']);
    }
  }
 ?>
