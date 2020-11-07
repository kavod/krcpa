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

        // Other else configurations.
        foreach ($config as $name => $value)
        {
            $this->setVariable($name, $value);
        }
    }

    public function auth_password(): bool
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
      if ($http_code == 200)
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

    public function query($service,$method='GET',$postfields=array(),$retry=true): array
    {
      $ch = curl_init();
      $opts = self::$CURL_OPTS;
      if ($method=='GET')
        $opts[CURLOPT_HTTPGET] = true;
      $querystring = '?api_version='.KRCPA_API_VERSION;
      $opts[CURLOPT_URL] = KRCPA_API_URL . $service . $querystring;
      $opts[CURLOPT_HTTPHEADER] = array();
      $opts[CURLOPT_HTTPHEADER][] = "content-type: application/x-www-form-urlencoded";
      $opts[CURLOPT_HTTPHEADER][] = "Authorization: " . $this->getVariable('token','');
      $opts[CURLOPT_HTTPHEADER][] = "User-agent: " . KRCPA_USER_AGENT;
      $str_postfields = '';
      foreach ($postfields as $key => $value) {
        if ($str_postfields != '')
          $str_postfields .= '&';
        $str_postfields .= urlencode($key).'='.urlencode($value);
      }
      if ($str_postfields!='')
        $opts[CURLOPT_POSTFIELDS] = $str_postfields;

      // print_r($opts);
      curl_setopt_array($ch, $opts);
      $result = curl_exec($ch);
      $errno = curl_errno($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      // print_r($result);
      // print_r($errno);
      if (!$errno) {
        switch ($http_code) {
          case 200:  # OK
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
            echo 'Unexpected HTTP code: ', $http_code, "\n";
            return array();
        }
      }
      if ($result === false)
      {
        return false;
      }
      list($headers, $body) = explode("\r\n\r\n", $result);
      $json = json_decode($body,true);
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
          return (count($devices['doorbots'])>0);
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

    public function getDeviceByAttr($attr,$value)
    {
      $devices = $this->getDevices();
      if (array_key_exists('doorbots',$devices))
      {
        foreach($devices['doorbots'] as $device)
        {
          if ($device->getVariable($attr,'')==$value)
            return $device;
        }
        return null;
      } else {
        return null;
      }
    }

    public function getDeviceById($value)
    {
      return $this->getDeviceByAttr('id',$value);
    }

    public function getDeviceByDeviceId($value)
    {
      return $this->getDeviceByAttr('device_id',$value);
    }

    public function getDevices()
    {
      $json = $this->query('ring_devices');
      $result = array(
        "doorbots" => array(),
        "chimes" => array()
      );
      if (array_key_exists('doorbots',$json))
      {
        foreach($json['doorbots'] as $doorbot)
        {
          $result['doorbots'][] = new krcpaDoorbot($this,$doorbot);
        }
      }
      if (array_key_exists('chimes',$json))
      {
        foreach($json['chimes'] as $chime)
        {
          $result['chimes'][] = new krcpaChime($this,$chime);
        }
      }
      return $result;
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
