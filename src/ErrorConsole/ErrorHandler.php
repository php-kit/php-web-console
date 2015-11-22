<?php
namespace PhpKit\WebConsole\ErrorConsole;

use PhpKit\WebConsole\ErrorConsole\Exceptions\PHPError;

/**
 * Displays debugging information when errors occur in dev.mode, or logs it when
 * in production mode.
 */
class ErrorHandler
{
  private static $nextErrorHandler;
  private static $nextExceptionHandler;

  public static function globalErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext)
  {
    if (!error_reporting ()) return false;

//    self::globalExceptionHandler (new PHPError($errno, $errstr, $errfile, $errline, $errcontext));
    if (self::$nextErrorHandler)
      call_user_func (self::$nextErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
    throw new PHPError($errno, $errstr, $errfile, $errline, $errcontext);
  }

  public static function globalExceptionHandler ($exception)
  {
    $handled = false;
    if (ErrorConsole::$debugMode) {
      ErrorConsole::display ($exception);
      $handled = true;
    }
    if (self::$nextExceptionHandler)
      call_user_func (self::$nextExceptionHandler, $exception);
    if (!$handled) {
      @ob_end_clean ();
      echo "<style>body{background:silver}table {font-family:Menlo,sans-serif;font-size:12px}</style>";
      throw $exception;
    }
    exit;
  }

  public static function init ()
  {
    self::$nextErrorHandler     = set_error_handler ([get_class (), 'globalErrorHandler']);
    self::$nextExceptionHandler = set_exception_handler ([get_class (), 'globalExceptionHandler']);
    register_shutdown_function ([get_class (), 'onShutDown']);
  }

  public static function onShutDown ()
  {
    //Catch fatal errors, which do not trigger globalErrorHandler()
    $error = error_get_last ();
    if (isset($error) && ($error['type'] == E_ERROR || $error['type'] == E_PARSE)) {
      //remove error output
      /*
      $buffer = @ob_get_clean ();
      $buffer = preg_replace ('#<table class=\'xdebug-error\'[\s\S]*?</table>#i', '', $buffer);
      echo $buffer;
      */
      self::globalExceptionHandler (new PHPError(1, $error['message'], $error['file'], $error['line']));
    }
  }

}
