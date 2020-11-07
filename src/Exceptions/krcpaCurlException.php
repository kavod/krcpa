<?php
namespace KRCPA\Exceptions;
class krcpaCurlException extends krcpaException
{
  function __construct($message,$code)
  {
    parent::__construct($message, $code);
  }

  function code_description()
  {
    return curl_strerror($this->code);
  }
}
?>
