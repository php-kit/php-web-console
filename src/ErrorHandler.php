<?php
namespace PhpKit\WebConsole;

use PhpKit\WebConsole\Renderers\ErrorPopupRenderer;
use Psr\Http\Message\ResponseInterface;

/**
 * Displays debugging information when errors occur in dev.mode, or logs it when
 * in production mode.
 */
class ErrorHandler
{
  const TRIM_WIDTH = 50;

  public static $appName   = 'PHP Web Console';
  public static $debugMode = true;
  public static $baseUri;

  private static $baseDir;
  private static $nextErrorHandler;
  private static $nextExceptionHandler;
  private static $pathsMap;

  public static function errorLink ($file, $line = 1, $col = 1, $label = '', $class = '')
  {
    if (empty($file))
      return '';
    $label = $label ?: self::shortFileName ($file);
    $file  = urlencode (self::toProjectPath ($file));
    --$line;
    --$col;
    $baseUri = self::$baseUri;
    return "<a class='$class' target='hidden' href='$baseUri/goto-source.php?file=$file&line=$line&col=$col'>$label</a>";
  }

  public static function globalErrorHandler ($errno, $errstr, $errfile, $errline, $errcontext)
  {
    if (ini_get ('error_reporting') == 0)
      return false;
//    self::globalExceptionHandler (new PHPError($errno, $errstr, $errfile, $errline, $errcontext));
    if (self::$nextErrorHandler)
      call_user_func (self::$nextErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
    throw new PHPError($errno, $errstr, $errfile, $errline, $errcontext);
  }

  public static function globalExceptionHandler ($exception)
  {
    $handled = false;
    if (self::$debugMode && WebConsole::$initialized) {
      self::showErrorPopup ($exception);
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

  public static function init ($debugMode = true, $baseDir = '', $pathsMap = [])
  {
    self::$baseDir              = $baseDir;
    self::$baseUri              = dirnameEx (get ($_SERVER, 'SCRIPT_NAME'));
    self::$pathsMap             = $pathsMap;
    self::$debugMode            = $debugMode;
    self::$nextErrorHandler     = set_error_handler ([get_class (), 'globalErrorHandler']);
    self::$nextExceptionHandler = set_exception_handler ([get_class (), 'globalExceptionHandler']);
    register_shutdown_function ([get_class (), 'onShutDown']);
  }

  public static function normalizePath ($path)
  {
    do {
      $path = preg_replace (
        ['#//|/\./#', '#/([^/]*)/\.\./#'],
        '/', $path, -1, $count
      );
    } while ($count > 0);
    return $path;
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

  public static function processMessage ($msg)
  {
    $msg = preg_replace_callback ('#<path>([^<]*)</path>#', function ($m) {
      return '<b>' . ErrorHandler::shortFileName ($m[1]) . '</b>';
    }, $msg);
    return $msg;
  }

  public static function setPathsMap (array $map)
  {
    self::$pathsMap = $map;
  }

  public static function shortFileName ($fileName)
  {
    $fileName = self::normalizePath ($fileName);

    foreach (self::$pathsMap as $from => $to)
      if (substr ($fileName, 0, $l = strlen ($from)) == $from) {
        $fileName = $to . substr ($fileName, $l);
      }

    if (strpos ($fileName, self::$baseDir) === 0)
      return substr ($fileName, strlen (self::$baseDir) + 1);

    $p = strpos ($fileName, '/vendor/');
    if ($p) return substr ($fileName, $p + 1);
    return $fileName;
  }

  /**
   * Outputs the error popup, or a plain message, depending on the response content type.
   * @param \Exception|\Error $exception Note: can't be type hinted, for PHP7 compat.
   * @param ResponseInterface|null $response If null, it outputs directly to the client. Otherwise, it assumes the
   *                                         object is a new blank response.
   * @return ResponseInterface
   */
  static function showErrorPopup ($exception, ResponseInterface $response = null)
  {
    // For HTML pages, output the error popup

    if (strpos (get ($_SERVER, 'HTTP_ACCEPT'), 'text/html') !== false) {
      ob_start ();
      ErrorPopupRenderer::renderStyles ();
      $stackTrace = self::getStackTrace ($exception->getPrevious() ? : $exception);
      ErrorPopupRenderer::renderPopup ($exception, self::$appName, $stackTrace);
      $popup = ob_get_clean ();

      // PSR-7 output

      if ($response) {
        $response->getBody ()->write ($popup);
        return $response->withStatus (500);
      }

      // Direct output

      echo $popup;
    }

    // For other content types, output a plain text message, replacing any existing response content
    else {

      // PSR-7 output

      if ($response) {
        $response->getBody ()->write ($exception->getMessage ());
        if (self::$debugMode)
          $response->getBody ()->write ("\n\nStack trace:\n" . $exception->getTraceAsString ());
        return $response
          ->withoutHeader ('Content-Type')
          ->withHeader ('Content-Type', 'text-plain')
          ->withStatus (500);
      }

      // Direct output

      header ("Content-Type: text/plain");
      http_response_code (500);
      echo $exception->getMessage ();
      if (self::$debugMode)
        echo "\n\nStack trace:\n" . $exception->getTraceAsString ();
    }
  }

  private static function debugVal ($arg)
  {
    switch (gettype ($arg)) {
      case 'boolean':
        $arg = $arg ? 'true' : 'false';
        break;
      case 'string':
        $arg = "'" . mb_strimwidth (htmlspecialchars ($arg), 0, self::TRIM_WIDTH, "...") . "'";
        break;
      case 'integer':
      case 'double':
        break;
      default:
        $arg = ucfirst (gettype ($arg));
    }
    return $arg;
  }

  private static function filterStackTrace (array $trace)
  {
    $namespace = WebConsole::getLibraryNamespace ();
    return array_values (array_filter ($trace, function ($frame) use ($namespace) {
      return !isset($frame['class']) || substr ($frame['class'], 0, strlen ($namespace)) != $namespace;
    }));
  }

  private static function getStackTrace ($exception)
  {
    ob_start ();
    $trace = self::filterStackTrace ($exception instanceof PHPError ? debug_backtrace () : $exception->getTrace ());
    if (count ($trace) && $trace[count ($trace) - 1]['function'] == '{main}')
      array_pop ($trace);
    foreach ($trace as $k => $v) {
      $class = isset($v['class']) ? $v['class'] : '';
      if ($class == 'ErrorHandler')
        continue;
      //$class   = $class ? "<span class='class'>$class</span>" : '';
      if (isset($v['function'])) {
        $f    = $v['function'];
        $type = isset($v['type']) ? $v['type'] : '->';
        if (strpos ($f, '{closure}') !== false) {
          if ($class) $fn = "<span class='info' title='On class $class'>Closure</span>";
          else $fn = "<span class='type'>Closure</span>";
        }
        else {
          if ($class) {
            $z         = explode ('\\', $class);
            $className = array_pop ($z);
            $namespace = implode ('\\', $z);
            $fn        = $f == '__construct'
              ? "new <span class='class' title='$namespace'>$className</span>"
              : "<span class='class' title='$namespace'>$className</span>$type<span class='fn'>$f</span>";
          }
          else $fn = "<span class='fn'>$f</span>";
        }
        $fn = "Call $fn";
      }
      else $fn = 'global scope';
      if (isset($v['function'])) {
        $args = [];
        if (isset($v['args'])) {
          foreach ($v['args'] as $arg) {
            switch (gettype ($arg)) {
              case 'boolean':
                $arg = $arg ? 'true' : 'false';
                break;
              case 'string':
                $arg = "'<span class='string'>" . mb_strimwidth (htmlspecialchars ($arg), 0, self::TRIM_WIDTH, "...") .
                       "</span>'";
                break;
              case 'integer':
              case 'double':
                break;
              case 'array':
                $arg = '<span class="info" title="' . (($arg) ? "[\n  " . htmlspecialchars (implode (",\n  ",
                      array_map (function ($k, $v) use ($arg) { return "$k => " . self::debugVal ($v); },
                        array_keys ($arg),
                        $arg))) . "\n]" : 'Empty array') . '">array</span>';
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
      $file    = isset($v['file']) ? $v['file'] : '';
      $fitems  = explode (DIRECTORY_SEPARATOR, $file);
      $fname   = '<span class="file">' . end ($fitems) . '</span>';
      $line    = isset($v['line']) ? $v['line'] : '&nbsp;';
      $lineStr = $line ? "<span class='line'>$line</span>" : '';
      $edit    = $file ? self::errorLink ($file, $line, 1, 'edit', '__btn') : '';
      $at      = $file ? self::errorLink ($file, $line, 1) : '&lt;unknown location&gt;';
      ErrorPopupRenderer::renderStackFrame ($fname, $lineStr, $fn, $args, $at, $edit);
    }
    return ob_get_clean ();
  }

  private static function toProjectPath ($path)
  {
    $path = self::shortFileName ($path);
    return $path[0] == '/' ? $path : self::$baseDir . '/' . $path;
  }
}
