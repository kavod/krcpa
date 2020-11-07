<?php
namespace KRCPA\Exceptions;
class krcpaApiException extends krcpaException
{
  public $http_code;
  public $error;
  public $body;

  function __construct($message,$code,$body=array())
  {
    $this->http_code = $code;
    $this->error = $message;
    if ($body == '') $body = array();
    $this->body = $body;
    parent::__construct($message, $code);
  }

  function code_description()
  {
    $http_codes = parse_ini_file(__DIR__ ."/http_codes.ini");
    return $http_codes[$this->http_code];
  }
}
?>
