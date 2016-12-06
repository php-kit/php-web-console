<?php
namespace PhpKit\WebConsole\ErrorConsole;

use Electro\Exceptions\ExceptionWithTitle;
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
    if (!error_reporting ()) {
      if (PHP_MAJOR_VERSION >= 7)
        error_clear_last ();
      return false;
    }

//    self::globalExceptionHandler (new PHPError($errno, $errstr, $errfile, $errline, $errcontext));
    if (self::$nextErrorHandler)
      call_user_func (self::$nextErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
    throw new PHPError($errstr, 0, $errno, $errfile, $errline, null, $errcontext);
  }

  public static function globalExceptionHandler ($e)
  {
    $handled = false;
    if (ErrorConsole::$devEnv) {
      ErrorConsole::display ($e);
      $handled = true;
    }
    if (self::$nextExceptionHandler)
      call_user_func (self::$nextExceptionHandler, $e);
    if (!$handled) {
      @ob_end_clean ();
      echo "<style>body,table{font-family:Menlo,sans-serif;font-size:12px}</style>";
      if ($e instanceof ExceptionWithTitle)
        echo "<h3>{$e->getTitle()}</h3>";
      printf ("<p>%s", $e->getMessage ());
      // Commented to prevent the display of sensitive information:
//      printf ("<p>%s<p>At <b>%s</b>, line <b>%d</b><p>Stack trace:<p><pre>%s", $e->getFile (), $e->getLine (), $e->getTraceAsString ());
    }
    exit;
  }

  public static function init ()
  {
    self::$nextErrorHandler     = set_error_handler ([static::class, 'globalErrorHandler'], E_ALL | E_STRICT);
    self::$nextExceptionHandler = set_exception_handler ([static::class, 'globalExceptionHandler']);
    register_shutdown_function ([static::class, 'onShutDown']);

    if (extension_loaded ('xdebug')) {
      //[0..4] collect the parameters passed to functions when a function call is recorded
      ini_set ('xdebug.collect_params', 1);
      //gather information about which variables are used in a certain scope
      ini_set ('xdebug.collect_vars', 0);
      ini_set ('xdebug.dump_globals', 0);
      //how many array keys and object's properties are shown
      ini_set ('xdebug.var_display_max_children', 99);
      //how many nested levels of array elements and object properties
      ini_set ('xdebug.var_display_max_depth', 5);
      //maximum string length that is shown when variables are displayed
      ini_set ('xdebug.var_display_max_data', 512);
      //maximum level of nested functions that are allowed
      ini_set ('xdebug.max_nesting_level', 500);
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
