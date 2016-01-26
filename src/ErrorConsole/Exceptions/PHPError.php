<?php
namespace PhpKit\WebConsole\ErrorConsole\Exceptions;

use ErrorException;
use Exception;

class PHPError extends ErrorException
{
  /** @var array */
  public $context;
  /** @var string */
  public $title;

  public function __construct ($message = "", $code = 0, $severity = E_ERROR, $filename = __FILE__,
                               $lineno = __LINE__, Exception $previous = null, array $errcontext = null)
  {
    switch ($severity) {
      case E_ERROR:
        $type = "Error";
        break;
      case E_NOTICE:
        $type = 'Notice';
        break;
      case E_STRICT:
        $type = 'Advice';
        break;
      case E_WARNING:
        $type = 'Warning';
        break;
      case E_DEPRECATED:
        $type = 'Deprecation Warning';
        break;
      default:
        $type = "error type $code";
    }
    $this->title   = "PHP $type";
    $this->context = $errcontext;
    parent::__construct ($message, $code, $severity, $filename, $lineno, $previous);
  }

  function getTitle ()
  {
    return $this->title;
  }

}
