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
        if (array_key_exists('doorbell_volume',$config['settings']))
        {
          $this->setVariable('volume',$config['settings']['doorbell_volume']);
        }
      }

      if (array_key_exists('battery_life',$config))
      {
        $this->setVariable('battery_life',$config['battery_life']);
      }
    }

    public function setVolume($vol)
    {
      $vol = intval($vol);
      $postfields = array('doorbot'=>array("settings"=>array("doorbell_volume"=>$vol)));
      $json = $this->query('doorbots/'.$this->getVariable('id'),$method='PUT',$postfields=$postfields);
      $this->setVariable('volume',$vol);
      return true;
    }

    public function refreshSnapshot()
    {
      $postfields = array('doorbot_ids'=>array($this->getVariable('id')),'refresh'=>true);
      $json = $this->query('snapshots/update_all',$method='PUT',$postfields=$postfields);
      return true;
    }

    public function getSnapshotTimestamp()
    {
      $postfields = array('doorbot_ids'=>array($this->getVariable('id')));
      $json = $this->query('snapshots/timestamps',$method='POST',$postfields=$postfields);
      return $json['timestamps'];
    }

    public function getSnapshot($saveto='')
    {
      if ($saveto == '')
        $saveto = sys_get_temp_dir().'/snapshot.jpg';
      $image = $this->query('snapshots/image/'.$this->getVariable('id'),$method='GET',$postfields=array(),$retry=true,$binary=true)[0];
      if(file_exists($saveto)){
          unlink($saveto);
      }
      $fp = fopen($saveto,'x');
      fwrite($fp, $image);
      fclose($fp);
      return $saveto;
    }
  }
 ?>
