<?php
/**
 * KRCPA Client - Ring.Com Api Client by Kavod
 *
 */
  namespace KRCPA\Clients;

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

    public function auth_password()
    {
      $this->auth('password');
    }

    public function auth_refresh()
    {
      $this->auth('refresh_token');
    }

    public function auth($grant_type)
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
            return false;
          }
          if ($this->getVariable('password',-1)==-1)
          {
            return false;
          }
          $postfields['username'] = $this->getVariable('username','');
          $postfields['password'] = $this->getVariable('password','');
          break;

        case 'refresh_token':
          if ($this->getVariable('refresh_token',-1)==-1)
          {
            return false;
          }
          $postfields['refresh_token'] = $this->getVariable('refresh_token','');
          break;

        default:
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
      curl_close($ch);
      // print_r($result);

      if ($result === false)
      {
        return false;
      }
      list($headers, $body) = explode("\r\n\r\n", $result);
      $json = json_decode($body,true);
      //print_r($json);
      $this->setVariable('token',$json['token_type'].' '.$json['access_token']);
      $this->setVariable('refresh_token',$json['refresh_token']);
      return $body;
    }

    public function toString()
    {
      return json_encode($this->conf);
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


    public function query($service): array
    {
      $ch = curl_init();
      $opts = self::$CURL_OPTS;
      $opts[CURLOPT_HTTPGET] = true;
      $querystring = '?api_version='.KRCPA_API_VERSION;
      $opts[CURLOPT_URL] = KRCPA_API_URL . $service . $querystring;
      $opts[CURLOPT_HTTPHEADER] = array();
      $opts[CURLOPT_HTTPHEADER][] = "content-type: application/x-www-form-urlencoded";
      $opts[CURLOPT_HTTPHEADER][] = "Authorization: " . $this->getVariable('token','');
      $opts[CURLOPT_HTTPHEADER][] = "User-agent: " . KRCPA_USER_AGENT;
      //print_r($opts);
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
            echo "nouveau token";
            $this->auth_refresh();
            return $this->query($service);
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

    public function getDevices()
    {
      $json = $this->query('ring_devices');
      $result = array();
      foreach($json['doorbots'] as $doorbot)
      {
        $result[] = new krcpaDoorbot($this,$doorbot);
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
