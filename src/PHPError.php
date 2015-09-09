<?php
namespace PhpKit\WebConsole;

use Exception;

class PHPError extends Exception
{
  /** @var string */
  public $title;
  /** @var array */
  public $context;

  public function __construct ($errno, $errstr, $errfile, $errline, $errcontext = null)
  {
    switch ($errno) {
      case E_ERROR:
        $type = "ERROR";
        break;
      case E_NOTICE:
        $type = 'NOTICE';
        break;
      case E_STRICT:
        $type = 'ADVICE';
        break;
      case E_WARNING:
        $type = 'WARNING';
        break;
      case E_DEPRECATED:
        $type = 'DEPRECATION WARNING';
        break;
      default:
        $type = "error type $errno";
    }
    $this->code    = $errno;
    $this->message = $errstr;
    $this->file    = $errfile;
    $this->line    = $errline;
    $this->title   = "PHP $type";
    $this->context = $errcontext;
  }

}
