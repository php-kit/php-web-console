<?php
namespace PhpKit\WebConsole\ErrorConsole;

use ErrorException;
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
    throw new PHPError($errstr, 0, $errno, $errfile, $errline, null, $errcontext);
  }

  public static function globalExceptionHandler ($exception)
  {
    $handled = false;
    if (ErrorConsole::$devEnv) {
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
    self::$nextErrorHandler     = set_error_handler ([static::class, 'globalErrorHandler'], E_ALL | E_STRICT);
    self::$nextExceptionHandler = set_exception_handler ([static::class, 'globalExceptionHandler']);
    register_shutdown_function ([static::class, 'onShutDown']);

    if (extension_loaded('xdebug')) {
      ini_set('xdebug.collect_params', 1);            //[0..4] collect the parameters passed to functions when a function call is recorded
      ini_set('xdebug.collect_vars', 0);              //gather information about which variables are used in a certain scope
      ini_set('xdebug.dump_globals', 0);
      ini_set('xdebug.var_display_max_children', 99); //how many array keys and object's properties are shown
      ini_set('xdebug.var_display_max_depth', 5);     //how many nested levels of array elements and object properties
      ini_set('xdebug.var_display_max_data', 512);    //maximum string length that is shown when variables are displayed
      ini_set('xdebug.max_nesting_level', 500);       //maximum level of nested functions that are allowed
    }
  }

  public static function onShutDown ()
  {
    //Catch fatal errors, which do not trigger globalErrorHandler()
    $error = error_get_last ();
    if (isset($error)) {
      //remove error output emitted by the PHP engine
      @ob_get_clean ();
      self::globalExceptionHandler (new PHPError($error['message'], 0, $error['type'], $error['file'], $error['line']));
    }
  }

}
