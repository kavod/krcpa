<?php
namespace KRCPA\Exceptions;
class krcpaException extends \Exception
{
  function __construct($message,$code)
  {
    parent::__construct($message, $code);
  }

  function code_description()
  {
    return $this->message;
  }
}
?>
