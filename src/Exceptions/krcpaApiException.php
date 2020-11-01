<?php
namespace KRCPA\Exceptions;
class krcpaApiException extends \Exception
{
  public $http_code;
  public $error;

  function __construct($message,$code)
  {
    $this->http_code = $code;
    $this->error = $message;
    parent::__construct($message, $code);
  }
}
?>
