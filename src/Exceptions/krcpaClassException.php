<?php
namespace KRCPA\Exceptions;
class krcpaClassException extends \Exception
{
  public $v1;
  public $v2;
  public static $msg_code = array(
    1 => 'Username not provided',
    2 => 'Password not provided',
    3 => 'Refresh Token not provided',
    4 => "Grant type %s not supported",
    5 => 'Refresh Token missing in auth response: %s'
  );
  function __construct($message='',$code=0,$v1='',$v2='')
  {
    if ($message == '')
      $message = sprintf(self::$msg_code[$code],$v1,$v2);
    parent::__construct($message, $code);
  }
}
?>
