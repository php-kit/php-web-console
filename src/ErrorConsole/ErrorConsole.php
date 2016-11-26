<?php
namespace PhpKit\WebConsole\ErrorConsole;

use PhpKit\WebConsole\ErrorConsole\Renderers\ErrorConsoleRenderer;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;

/**
 * Displays debugging information when errors occur in dev.mode, or logs it when
 * in production mode.
 */
class ErrorConsole
{
  const TOOLTIP_MAX_LEN = self::TRIM_WIDTH * 10;
  const TRIM_WIDTH      = 50;
  /**
   * @var bool To be read by ErrorHandler::globalExceptionHandler()
   */
  public static  $devEnv     = true;
  private static $EDITOR_URL = '';
  private static $appName    = 'PHP Web Console';
  private static $baseDir;
  private static $baseUri;
  private static $pathsMap;

  /**
   * Outputs the error popup, or a plain message, depending on the response content type.
   *
   * @param \Exception|\Error      $exception Note: can't be type hinted, for PHP7 compat.
   * @param ResponseInterface|null $response  If null, it outputs directly to the client. Otherwise, it assumes the
   *                                          object is a new blank response.
   * @return ResponseInterface|null
   */
  static function display ($exception, ResponseInterface $response = null)
  {
    // For HTML pages, output the error popup

    if (strpos (get ($_SERVER, 'HTTP_ACCEPT'), 'text/html') !== false) {
      ob_start ();
      ErrorConsoleRenderer::renderStyles ();
      $stackTrace = self::getStackTrace ($exception->getPrevious () ?: $exception);
      ErrorConsoleRenderer::renderPopup ($exception, self::$appName, $stackTrace);
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
        if (self::$devEnv)
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
      if (self::$devEnv)
        echo "\n\nStack trace:\n" . $exception->getTraceAsString ();
    }
    return null;
  }

  public static function errorLink ($file, $line = 1, $col = 1, $label = '', $class = 'hint--rounded hint--top',
                                    $tooltipAttr = 'data-hint')
  {
    if (empty($file))
      return '';
    $path    = self::shortFileName ($file);
    $label   = $label ?: $path;
    $file    = urlencode (self::toProjectPath ($file));
    $baseUri = self::$baseUri;
    $url     = self::$EDITOR_URL
      ? "$baseUri/" . self::$EDITOR_URL . "?file=$file&line=$line&col=$col"
      : 'javascript:void(0)';
    return "<a class='$class' target='hidden' $tooltipAttr='$path' href='$url'>$label</a>";
  }

  public static function init ($devEnv = true, $baseDir = '', $pathsMap = [])
  {
    self::$baseDir  = $baseDir;
    self::$baseUri  = dirnameEx (get ($_SERVER, 'SCRIPT_NAME'));
    self::$pathsMap = $pathsMap;
    self::$devEnv   = $devEnv;
    ErrorHandler::init ();
  }

  /**
   * For use by renderers.
   *
   * @param string $msg
   * @return string
   */
  public static function processMessage ($msg)
  {
    $msg = preg_replace_callback ('#<path>([^<]*)</path>#', function ($m) {
      return ErrorConsole::errorLink ($m[1], 1, 1, basename ($m[1]));
    }, $msg);
    return $msg;
  }

  /**
   * @param string $appName
   */
  public static function setAppName ($appName)
  {
    self::$appName = $appName;
  }

  /**
   * Sets the virtual URL for an HTTP request for opening a source file on an editor at the error location.
   *
   * <p>Example: `'edit-source'` for generating 'edit-source?file=filename.php&line=8&col=1
   *
   * @param string $expr
   */
  static function setEditorURL ($expr)
  {
    self::$EDITOR_URL = $expr;
  }

  public static function setPathsMap (array $map)
  {
    self::$pathsMap = $map;
  }

  public static function shortFileName ($fileName)
  {
    $fileName = self::normalizePath ($fileName);

    if (self::$pathsMap)
      foreach (self::$pathsMap as $from => $to)
        if (substr ($fileName, 0, $l = strlen ($from)) == $from) {
          $fileName = $to . substr ($fileName, $l);
        }

    if (strpos ($fileName, self::$baseDir) === 0)
      return substr ($fileName, strlen (self::$baseDir) + 1);

    return $fileName;
  }

  private static function debugVal ($arg)
  {
    switch (gettype ($arg)) {
      case 'boolean':
        $arg = $arg ? 'true' : 'false';
        break;
      case 'string':
        $arg = "'" . htmlspecialchars (mb_strimwidth ($arg, 0, self::TRIM_WIDTH, "...")) . "'";
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
    $n = explode ('\\', Debug::libraryNamespace ());
    array_pop ($n);
    $namespace = implode ('\\', $n);
    return array_values (array_filter ($trace, function ($frame) use ($namespace) {
      return !isset($frame['class']) || substr ($frame['class'], 0, strlen ($namespace)) != $namespace;
    }));
  }

  /**
   * @param \Exception $exception
   * @return string
   */
  private static function getStackTrace ($exception)
  {
    ob_start ();
//    $trace = self::filterStackTrace ($exception instanceof PHPError ? debug_backtrace () : $exception->getTrace ());
    $trace = $exception->getTrace ();
    if (count ($trace) && $trace[count ($trace) - 1]['function'] == '{main}')
      array_pop ($trace);
    $count = count ($trace);
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
                $arg = mb_strlen ($arg) > self::TRIM_WIDTH
                  ? sprintf ("'<span class='string hint--rounded hint--top' data-hint='%s'>%s</span>'",
                    htmlspecialchars (mb_strimwidth (
                      chunk_split ($arg, self::TRIM_WIDTH, "\n"),
                      0, self::TOOLTIP_MAX_LEN, "..."
                    ), ENT_QUOTES),
                    htmlspecialchars (mb_strimwidth ($arg, 0, self::TRIM_WIDTH, "...")))
                  : sprintf ("'<span class='string'>%s</span>'", htmlspecialchars ($arg, ENT_QUOTES));
                break;
              case 'integer':
              case 'double':
                break;
              case 'array':
                $arg = '<span class="info __type hint--rounded hint--top" data-hint="' .
                       (($arg) ? "[\n  " . htmlspecialchars (implode (",\n  ",
                           array_map (function ($k, $v) use ($arg) { return "$k => " . self::debugVal ($v); },
                             array_keys ($arg),
                             $arg))) . "\n]" : 'Empty array') . '">array</span>';
                break;
              default:
                if (is_object ($arg))
                  switch (get_class ($arg)) {
                    case \ReflectionMethod::class:
                      /** @var \ReflectionMethod $arg */
                      $arg = sprintf ('<span class=type>ReflectionMethod</span>&lt;%s::%s>',
                        $arg->getDeclaringClass ()->getName (), $arg->getName ());
                      break;
                    case \ReflectionFunction::class:
                      /** @var \ReflectionFunction $arg */
                      $arg = sprintf ('<span class=type>ReflectionFunction</span>&lt;function at %s>',
                        self::errorLink ($arg->getFileName (), $arg->getStartLine (), 1,
                          sprintf ('%s line %d', basename ($arg->getFileName ()), $arg->getStartLine ()),
                          'tag hint--rounded hint--top'
                        ));
                      break;
                    case \ReflectionParameter::class:
                      /** @var \ReflectionParameter $arg */
                      $arg = sprintf ('<span class=type>ReflectionParameter</span>&lt;$%s>', $arg->getName ());
                      break;
                    default:
                      $arg = Debug::typeInfoOf ($arg);
                  }
                else $arg = Debug::typeInfoOf ($arg);
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
      ErrorConsoleRenderer::renderStackFrame ($count - $k, $fname, $lineStr, $fn, $args, $at, $edit);
    }
    return ob_get_clean ();
  }

  private static function normalizePath ($path)
  {
    do {
      $path = preg_replace (
        ['#//|/\./#', '#/([^/]*)/\.\./#'],
        '/', $path, -1, $count
      );
    } while ($count > 0);
    return $path;
  }

  private static function toProjectPath ($path)
  {
    $path = self::shortFileName ($path);
    return $path[0] == '/' ? $path : self::$baseDir . '/' . $path;
  }
}
