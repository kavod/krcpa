<?php
namespace KRCPA\Exceptions;
class krcpaCurlException extends \Exception
{
  function __construct($message,$code)
  {
    parent::__construct($message, $code);
  }
}
?>
