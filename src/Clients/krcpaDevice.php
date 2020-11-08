<?php
/**
 * KRCPA Device - Ring.Com Api Device by Kavod
 *
 */
  namespace KRCPA\Clients;

  class krcpaDevice extends krcpaClient
  {
    public $conf = array();
    protected $client;

    public function __construct($client,$conf = array())
    {
      $this->client = $client;

      $this->conf = array_merge(array(),$client->conf);
      $this->update_conf($conf);
      $this->enhance_features();
    }

    public function is_featured($feature)
    {
      $features = $this->getVariable('features',array());
      if (array_key_exists($feature,$features))
      {
        return $features[$feature];
      }
      return false;
    }

    public function resync()
    {
      $this->client->getDevices($resync=true);
      $this->enhance_features();
    }

    public function getVolume()
    {
      if ($this->is_featured('volume'))
        return $this->getVariable('volume',-1);
      return -1;
    }

    public function update_conf($config = array())
    {
      parent::update_conf($config);

      if (array_key_exists('id',$config))
      {
        $this->setVariable('id',$config['id']);
      }

      if (array_key_exists('device_id',$config))
      {
        $this->setVariable('device_id',$config['device_id']);
      }

      if (array_key_exists('description',$config))
      {
        $this->setVariable('description',$config['description']);
      }

      if (array_key_exists('kind',$config))
      {
        $this->setVariable('kind',$config['kind']);
      }

      if (array_key_exists('features',$config))
      {
        $this->setVariable('features',$config['features']);
      }
    }

  }
 ?>
