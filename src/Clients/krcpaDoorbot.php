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

    public function enhance_features()
    {
      // Battery
      $this->conf['battery'] = true;

      // Ring
      $this->conf['features']['ring'] = true;

      // Volume
      $this->conf['features']['volume'] = true;
    }

    public function update_conf($config = array())
    {
      parent::update_conf($config);
      if (array_key_exists('settings',$config))
      {
        if (array_key_exists('volume',$config['settings']))
        {
          $this->setVariable('volume',$config['settings']['doorbell_volume']);
        }
      }

      if (array_key_exists('battery_life',$config))
      {
        $this->setVariable('battery_life',$config['battery_life']);
      }
    }
  }
 ?>
