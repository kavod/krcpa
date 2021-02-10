<?php
namespace KRCPA\Exceptions;
class krcpaApiException extends krcpaException
{
  public $http_code;
  public $error;
  public $body;
  public $headers;
  public $opts;

  function __construct($message,$code,$body=array(),$headers='',$opts=array())
  {
    $this->http_code = $code;
    $this->error = $message;
    $this->headers = $headers;
    $this->opts = $opts;
    if ($body == '') $body = array();
    $this->body = $body;
    parent::__construct($message, $code);
  }

  function code_description()
  {
    $http_codes = parse_ini_file(__DIR__ ."/http_codes.ini");
    return $http_codes[$this->http_code];
  }

  public function __toString ( ) : string
  {
    $return = parent::__toString();
    $return .= print_r($opts,true);
    $return .= print_r($headers,true);
    $return .= print_r($body,true);
    return $return;
  }
}
?>
