<?php
/**
 * KRCPA Client - Ring.Com Api Client by Kavod
 *
 */
  namespace KRCPA\Clients;
  use KRCPA\Exceptions\krcpaApiException;
  use KRCPA\Exceptions\krcpaClassException;

  define('KRCPA_VERSION',"0.1");
  define('KRCPA_API_URL', "https://api.ring.com/clients_api/");
  define('KRCPA_OAUTH_URL', "https://oauth.ring.com/oauth/token");
  define('KRCPA_API_VERSION', "9");
  define('KRCPA_USER_AGENT','ring/5.21.0.5 CFNetwork/1121.2.2 Darwin/19.2.0');

  class krcpaClient
  {
    public $conf = array();
    protected $token;
    public $_devices = array();

    /**
    * Default options for cURL.
    */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => KRCPA_USER_AGENT,
        CURLOPT_SSL_VERIFYPEER => TRUE,
        CURLOPT_HTTPHEADER     => array(
            "Content-Type: application/json"
          )
    );

    /**
    * Initialize a KKPA Client.
    *
    * @param $config
    *   An associative array as below:
    *   - username: (optional) The username.
    *   - password: (optional) The password.
    */
    public function __construct($conf = array())
    {
        $config = array_merge(array(),$conf);

        $this->update_conf($config);
    }

    public function update_conf($config = array())
    {
      // Other else configurations.
      foreach ($config as $name => $value)
      {
          $this->setVariable($name, $value);
      }
    }

    public function auth_password(): array
    {
      return $this->auth('password');
    }

    public function auth_refresh($token = null): array
    {
      if (!is_null($token))
      {
        $this->setVariable('refresh_token',$token);
      }
      return $this->auth('refresh_token');
    }

    public function auth($grant_type): array
    {
      $opts = self::$CURL_OPTS;
      $postfields = array(
        "client_id" => "ring_official_android",
        "grant_type" => $grant_type,
        "scope" => "client"
      );
      switch($grant_type)
      {
        case 'password':
          if ($this->getVariable('username',-1)==-1)
          {
            // Username not provided
            throw new krcpaClassException('',$code=1);
            return false;
          }
          if ($this->getVariable('password',-1)==-1)
          {
            // Password not provided
            throw new krcpaClassException('',$code=2);
            return false;
          }
          $postfields['username'] = $this->getVariable('username','');
          $postfields['password'] = $this->getVariable('password','');
          break;

        case 'refresh_token':
          if ($this->getVariable('refresh_token',-1)==-1)
          {
            // Refresh Token not provided
            throw new krcpaClassException('',$code=3);
            return false;
          }
          $postfields['refresh_token'] = $this->getVariable('refresh_token','');
          break;

        default:
          // Grant type %s not supported
          throw new krcpaClassException($code=4,$v1=$grant_type);
          return false;
          break;
      }

      $ch = curl_init();
      $opts = self::$CURL_OPTS;
      $opts[CURLOPT_POSTFIELDS] = json_encode($postfields);
      $opts[CURLOPT_HTTPHEADER] = array();
      $opts[CURLOPT_HTTPHEADER][] = "Content-Type: application/json";
      if ($this->getVariable('auth_code','') != '')
        $opts[CURLOPT_HTTPHEADER][] = "2fa-code: ".$this->getVariable('auth_code','');
      $opts[CURLOPT_URL] = KRCPA_OAUTH_URL;

      //print_r($opts);
      curl_setopt_array($ch, $opts);
      $result = curl_exec($ch);
      $errno = curl_errno($ch);
      $error = curl_error($ch);
      $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
      curl_close($ch);
      // print_r($result);

      if ($result === false)
      {
        throw new krcpaCurlException($error,$errno);
      }
      list($headers, $body) = explode("\r\n\r\n", $result);
      $json = json_decode($body,true);
      //print_r($json);
      if (substr($http_code,0,1) == '2')
      {
        if (array_key_exists('access_token',$json))
        {
          $this->setVariable('token',$json['token_type'].' '.$json['access_token']);
          $this->setVariable('refresh_token',$json['refresh_token']);
        } else {
          // Refresh Token missing in auth response: %s
          throw new krcpaClassException('',$code=5,$v1=print_r($body,true));
          return false;
        }
        //return $json;
        return $json;
      } else {
        $message = (array_key_exists('error_description',$json)) ? $json['error_description'] : '';
        throw new krcpaApiException($message,$http_code,$json);
      }

    }

    public function toString()
    {
      return json_encode($this->conf);
    }

    public function query($service,$method='GET',$postfields=array(),$retry=true,$binary=false): array
    {
      $ch = curl_init();
      $opts = self::$CURL_OPTS;
      if ($method=='GET')
        $opts[CURLOPT_HTTPGET] = true;
      elseif($method=='PUT')
        $opts[CURLOPT_CUSTOMREQUEST] = "PUT";
      if ($binary)
      {
        $opts[CURLOPT_RETURNTRANSFER] = 1;
        $opts[CURLOPT_BINARYTRANSFER] = 1;
      }
      $querystring = '?api_version='.KRCPA_API_VERSION;
      $opts[CURLOPT_URL] = KRCPA_API_URL . $service . $querystring;
      $opts[CURLINFO_HEADER_OUT] = true;
      $opts[CURLOPT_HTTPHEADER] = array();
	    $opts[CURLOPT_HTTPHEADER][] = "content-type: application/json; charset=utf-8";
      $opts[CURLOPT_HTTPHEADER][] = "Authorization: " . $this->getVariable('token','');
      $opts[CURLOPT_HTTPHEADER][] = "User-agent: " . KRCPA_USER_AGENT;
      // print_r($postfields);
      if ($postfields!=array())
        $opts[CURLOPT_POSTFIELDS] = json_encode($postfields);

      // print_r($opts);
      curl_setopt_array($ch, $opts);
      $result = curl_exec($ch);
      $errno = curl_errno($ch);
      $error = curl_error($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $info = curl_getinfo($ch);
      // print_r($info);
      curl_close($ch);
      // print_r($result);
      // print_r($errno);
      if ($result === false)
      {
        throw new krcpaCurlException($error,$errono);
      }
      list($headers, $body) = explode("\r\n\r\n", $result);
      switch($info['content_type'])
      {
        case 'application/json':
        case 'application/json; charset=utf-8':
          $json = json_decode($body,true);
          break;
        default:
          $json = array($body);
      }
      if ($json == null)
        $json = array();
      if (!$errno) {
        switch ($http_code) {
          case 200:  # OK
          case 204:  # OK - No content
            break;
          case 401: # Unauthorized
            if ($retry)
            {
              //echo "nouveau token";
              if(!$this->auth_refresh())
                return array();
              return $this->query($service,$retry=false);
            } else {
              return array();
            }
          default:
            $message = (array_key_exists('error_description',$json)) ? $json['error_description'] : '';
            throw new krcpaApiException($message,$http_code,$json);
            //echo 'Unexpected HTTP code: ', $http_code, "\n";
            return array();
        }
      }

      // print_r($json);

      return $json;
    }

    public function isAuth()
    {
      if ($this->getVariable('refresh_token','')=='')
      {
        return false;
      } else {
        try{
          $devices = $this->getDevices();
        } catch(krcpaApiException $e)
        {
          return false;
        }
        if (array_key_exists('doorbots',$devices))
        {
          return (count($devices)>0);
        } else {
          return false;
        }

      }
    }

    // Getters
    /**
     * Returns a persistent variable.
     *
     * To avoid problems, always use lower case for persistent variable names.
     *
     * @param $name
     *   The name of the variable to return.
     * @param $default
     *   The default value to use if this variable has never been set.
     *
     * @return
     *   The value of the variable.
     */
    public function getVariable($name, $default = NULL)
    {
        return array_key_exists($name,$this->conf) ? $this->conf[$name] : $default;
    }

    public static function getVersion() {
      return KRCPA_VERSION;
    }

    public function getDeviceByAttr($attr,$value,$resync=false)
    {
      foreach($this->_devices as $device)
      {
        if ($device->getVariable($attr,'')==$value)
          return $device;
      }
      if (count($this->_devices)==0 || $resync)
      {
        $devices = $this->getDevices($resync=true);
        foreach($devices['doorbots'] as $device)
        {
          if ($device->getVariable($attr,'')==$value)
            return $device;
        }
        foreach($devices['chimes'] as $device)
        {
          if ($device->getVariable($attr,'')==$value)
            return $device;
        }
      }
      throw new krcpaClassException('',6,$attr,$value);
      return null;
    }

    public function getDeviceById($value,$resync=false)
    {
      return $this->getDeviceByAttr('id',$value,$resync=$resync);
    }

    public function getDeviceByDeviceId($value,$resync=false)
    {
      return $this->getDeviceByAttr('device_id',$value,$resync=$resync);
    }

    public function getDevices($resync=false)
    {
      if ($resync || count($this->_devices)==0)
      {
        $json = $this->query('ring_devices');
        $result = array(
          "doorbots" => array(),
          "chimes" => array()
        );
        if (array_key_exists('doorbots',$json))
        {
          foreach($json['doorbots'] as $doorbot_conf)
          {
            if(count($this->_devices)>0)
            {
              try {
                $doorbot = $this->getDeviceById($doorbot_conf['id'],$resync=false);
                $doorbot->update_conf($doorbot_conf);
              } catch (krcpaClassException $e)
              {
                $doorbot = new krcpaDoorbot($this,$doorbot_conf);
                $this->_devices[] = $doorbot;
              }
            } else {
              $doorbot = new krcpaDoorbot($this,$doorbot_conf);
              $this->_devices[] = $doorbot;
            }
            $result['doorbots'][] = $doorbot;
          }
        }
        if (array_key_exists('chimes',$json))
        {
          foreach($json['chimes'] as $chime_conf)
          {
            if(count($this->_devices)>0)
            {
              try {
                $chime = $this->getDeviceById($chime_conf['id'],$resync=false);
                $chime->update_conf($chime_conf);
              } catch (krcpaClassException $e)
              {
                $chime = new krcpaChime($this,$chime_conf);
                $this->_devices[] = $chime;
              }
            } else {
              $chime = new krcpaChime($this,$chime_conf);
              $this->_devices[] = $chime;
            }
            $result['chimes'][] = $chime;
          }
        }
        return $result;
      } else {
        $result = array();
        foreach($this->_devices as $device)
        {
          switch($device->getVariable('kind'))
          {
            case 'chime':
              $result['chimes'][] = $device;
              break;
            case 'doorbell_v4':
              $result['doorbots'][] = $device;
              break;
          }
        }
        return $result;
      }

    }

    public function getHistory():array
    {
      $arr_history = $this->query('doorbots/history');
      $result = array();
      foreach($arr_history as $conf_history)
      {
        $result[] = new krcpaHistory($this,$conf_history);
      }
      return $result;
    }

    public function getActiveDings():array
    {
      $json_dings = $this->query('dings/active');
      $dings = array();
      foreach($json_dings as $ding)
      {
        $dings[] = new krcpaDing($this,$ding);
      }
      return $dings;
    }

    // Setters

    /**
    * Sets a persistent variable.
    *
    * To avoid problems, always use lower case for persistent variable names.
    *
    * @param $name
    *   The name of the variable to set.
    * @param $value
    *   The value to set.
    */
    public function setVariable($name, $value)
    {
        $this->conf[$name] = $value;
        return $this;
    }
  }

 ?>
