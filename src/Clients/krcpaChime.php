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
      $this->conf['features']['ring'] = false;

      // Do not Disturb
      $this->conf['features']['dnd'] = true;

      // Volume
      $this->conf['features']['volume'] = true;
      $this->setVariable('volume',$conf['settings']['volume']);
    }

    public function getDoNotDisturb()
    {
      $json = $this->query('chimes/'.$this->getVariable('id').'/do_not_disturb');
      if (array_key_exists('time_remaining',$json))
      {
        return $json['time_remaining'];
      }
      return false;
    }

    public function setDoNotDisturb($time=0)
    {
      $postfields = array('time'=>$time);
      $json = $this->query('chimes/'.$this->getVariable('id').'/do_not_disturb',$method='GET',$postfields=$postfields);
      if (array_key_exists('time_remaining',$json))
      {
        return $json['time_remaining'];
      }
      return false;
    }
  }
 ?>
