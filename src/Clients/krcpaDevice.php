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

      $config = array_merge(array(),$conf);

      $this->setVariable('id',$conf['id']);
      $this->setVariable('device_id',$conf['device_id']);
      $this->setVariable('description',$conf['description']);
      $this->setVariable('kind',$conf['kind']);
      $this->setVariable('features',$conf['features']);
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
  }
 ?>
