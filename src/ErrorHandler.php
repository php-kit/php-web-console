<?php
namespace Impactwave\WebConsole;

use Exception;
use Impactwave\WebConsole\Renderers\ErrorPopupRenderer;

/**
 * Displays debugging information when errors occur in dev.mode, or logs it when
 * in production mode.
 */
class ErrorHandler
{
  public static $appName   = 'PHP Web Console';
  public static $debugMode = true;

  private static $baseDir;
  private static $nextExceptionHandler;
  private static $nextErrorHandler;

  public static function init ($debugMode = true, $baseDir = '')
  {
    self::$baseDir              = $baseDir;
    self::$debugMode            = $debugMode;
    self::$nextErrorHandler     = set_error_handler ([get_class (), 'globalErrorHandler']);
    self::$nextExceptionHandler = set_exception_handler ([get_class (), 'globalExceptionHandler']);
    register_shutdown_function ([get_class (), 'onShutDown']);
  }

  public static function globalErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext)
  {
    if (ini_get ('error_reporting') == 0)
      return false;
    self::globalExceptionHandler (new PHPError($errno, $errstr, $errfile, $errline, $errcontext));
    if (self::$nextErrorHandler)
      call_user_func (self::$nextErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
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

  public static function globalExceptionHandler (Exception $exception)
  {
    global $application;
    if ($_SERVER['HTTP_HOST'] == 'localhost' || (isset($application) && $application->debugMode))
      self::showErrorPopup ($exception);
    if (self::$nextExceptionHandler)
      call_user_func (self::$nextExceptionHandler, $exception);
    exit;
  }

  public static function shortFileName ($fileName)
  {
    if (self::$baseDir) {
      if (strpos ($fileName, self::$baseDir) === 0)
        return substr ($fileName, strlen (self::$baseDir) + 1);
    }
    $p = strpos ($fileName, '/vendor/');
    if ($p) return substr ($fileName, $p + 1);
    return $fileName;
  }

  public static function errorLink ($file, $line = 1, $col = 1, $label = '', $class = '')
  {
    global $application;
    if (empty($file))
      return '';
    $label = $label ?: self::shortFileName ($file);
    $file  = urlencode ($file);
    --$line;
    --$col;
    return "<a class='$class' target='hidden' href='$application->baseURI/goto-source.php?file=$file&line=$line&col=$col'>$label</a>";
  }

  private static function showErrorPopup (Exception $exception)
  {
    ob_clean ();
    ErrorPopupRenderer::renderStyles ();
    $stackTrace = self::getStackTrace ($exception);
    ErrorPopupRenderer::renderPopup ($exception, self::$appName, $stackTrace);
    WebConsole::outputContent ();
  }

  private static function filterStackTrace (array $trace)
  {
    $me = get_class ();
    return array_values (array_filter ($trace, function ($frame) use ($me) {
      return !isset($frame['class']) || $frame['class'] != $me;
    }));
  }

  private static function getStackTrace (Exception $exception)
  {
    ob_start ();
    $trace = self::filterStackTrace ($exception instanceof PHPError ? debug_backtrace () : $exception->getTrace ());
    if (function_exists ('xdebug_get_function_stack')) {
      $trace2 = self::filterStackTrace (array_reverse (xdebug_get_function_stack ()));
      if (count ($trace2) > count ($trace))
        $trace = $trace2;
    }
    if (count ($trace) && $trace[count ($trace) - 1]['function'] == '{main}')
      array_pop ($trace);
    foreach ($trace as $k => $v) {
      $fn    = isset($v['function']) ? "<span class='fn'>{$v['function']}</span>" : 'global scope';
      $class = isset($v['class']) ? $v['class'] : '';
      if ($class == 'ErrorHandler')
        continue;
      if (isset($v['function'])) {
        $args = [];
        if (isset($v['args'])) {
          foreach ($v['args'] as $arg) {
            switch (gettype ($arg)) {
              case 'boolean':
                $arg = $arg ? 'true' : 'false';
                break;
              case 'string':
                $arg = "'$arg'";
                break;
              case 'integer':
              case 'double':
                break;
              case 'array':
                $arg = '[' . substr (var_export ($arg, true), 10, -3) . ']';
                break;
              default:
                $arg = ucfirst (gettype ($arg));
            }
            $args[] = $arg;
          }
        }
        $args = implode (", ", $args);
        $args = "($args)";
      }
      else $args = '';
      $class   = $class ? "<span class='class'>$class</span>->" : '';
      $file    = isset($v['file']) ? $v['file'] : '';
      $fitems  = explode (DIRECTORY_SEPARATOR, $file);
      $fname   = '<span class="file">' . end ($fitems) . '</span>';
      $line    = isset($v['line']) ? $v['line'] : '';
      $lineStr = $line ? "<span class='line'>$line</span>" : '';
      $at      = $file ? self::errorLink ($file, $line, 1) : '&lt;unknown location&gt;';
      ErrorPopupRenderer::renderStackFrame ($fname, $lineStr, $class, $fn, $args, $at);
    }
    return ob_get_clean ();
  }
}
