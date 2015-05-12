<?php
namespace Impactwave\WebConsole;

/*
 * Provides the display of debugging information on a panel on the bottom
 * of the browser window.
 */
use Exception;
use Impactwave\WebConsole\Renderers\WebConsoleRenderer;
use Psr\Http\Message\ResponseInterface;

class WebConsole
{
  static $TABLE_MAX_DEPTH      = 7;
  static $TABLE_COLLAPSE_DEPTH = 4;
  static $TABLE_PROP_WIDTH     = 170;
  static $TABLE_INDEX_WIDTH    = 50;
  static $TABLE_TYPE_WIDTH     = 100;

  static $class;

  private static $debugMode;

  /**
   * Map of panel names (identifiers) to Console subclass instances.
   * @var ConsolePanel[]
   */
  private static $panels = [];

  static function init ($debugMode = true)
  {
    self::$class     = get_class ();
    self::$debugMode = $debugMode;
    self::registerPanel ('console', new ConsolePanel ('Console', 'fa fa-terminal'));
  }

  public static function registerPanel ($panelId, ConsolePanel $panel)
  {
    self::$panels[$panelId] = $panel;
  }

  /**
   * Renders the console and inserts its content into the server response, if applicable.
   */
  public static function outputContent ()
  {
    if (self::$debugMode) {
      $content = ob_get_clean ();
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      echo preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      if (!$count)
        echo $myContent;
    }
    else error_log (self::panel ('console')->render ());
  }

  /**
   * Renders the console and inserts its content into the server response, if applicable.
   * @param ResponseInterface $response Optional HTTP response object if the host application is PSR-7 compliant.
   * @return ResponseInterface|null The modified response, or NULL if the $response argument was not given.
   */
  public static function outputContentViaResponse (ResponseInterface $response)
  {
    if (self::$debugMode) {
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      if ($response) {
        if ($response->getHeader ('Content-Type') == 'text/html') {
          $body = $response->getBody ();
          try {
            $body->rewind ();
          } catch (Exception $e) {
          }
          $content = $body->getContents ();
          $content = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
          if (!$count)
            $content .= $myContent;
          try {
            $body->rewind ();
          } catch (Exception $e) {
          }
          $body->write ($content);
          return $response->withHeader ('Content-Length', strlen ($content));
        }
      }
      $content = ob_get_clean ();
      echo preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      if (!$count)
        echo $myContent;
      return null;
    }
    else error_log (self::panel ('console')->render ());
    return $response;
  }

  public static function throwErrorWithLog (Exception $e)
  {
    $class              = get_class ($e);
    $openLogPaneMessage =
      "<p><a href='javascript:void(0)' onclick='document.getElementById(&quot;__console&quot;).style.height=&quot;auto&quot;'>Open the log pane</a> to see more details.";
    throw new $class($e->getMessage () . $openLogPaneMessage, $e->getCode (), $e);
  }

  public static function panel ($panelId)
  {
    if (isset(self::$panels[$panelId]))
      return self::$panels[$panelId];
    throw new Exception ("Invalid panel id: <b>" . htmlentities ($panelId) . '</b>');
  }

  public static function write ($panelId, $msg)
  {
    self::panel ($panelId)->write ($msg);
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   *
   * Extra params: list of one or more values to be displayed.
   * @param string $panelId Which panel to write to.
   * @return void
   */
  public static function debug ($panelId)
  {
    $args = array_slice (func_get_args (), 1);
    call_user_func_array ([self::panel ($panelId), 'debug'], $args);
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   *
   * > The filter function may remove some keys from the tabular output of objects or arrays.
   *
   * Extra params: list of one or more values to be displayed.
   * @param string   $panelId Which panel to write to.
   * @param callable $fn Filter callback. Receives the key name, the value and the target object/array.
   *                     Returns <code>true</code> if the value should be displayed.
   * @throws Exception
   */
  public static function debugWithFilter ($panelId, callable $fn)
  {
    $args = array_slice (func_get_args (), 1);
    call_user_func_array ([self::panel ($panelId), 'debugWithFilter'], $args);
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   *
   * Extra params: a title followed by a list of one or more values to be displayed.
   * @param string $panelId Which panel to write to.
   */
  public static function debugSection ($panelId)
  {
    $args = array_slice (func_get_args (), 1);
    call_user_func_array ([self::panel ($panelId), 'debugSection'], $args);
  }

  /**
   * Writes to a section on the specified panel.
   *
   * Extra params: a title followed by a list of one or more values to be displayed.
   * @param string $panelId Which panel to write to.
   */
  public static function logSection ($panelId)
  {
    $args = array_slice (func_get_args (), 1);
    call_user_func_array ([self::panel ($panelId), 'logSection'], $args);
  }

  /**
   * Writes to the specified panel.
   *
   * @param string $panelId Which panel to write to.
   */
  public static function log ($panelId)
  {
    $args = array_slice (func_get_args (), 1);
    call_user_func_array ([self::panel ($panelId), 'log'], $args);
  }

  public static function highlight ($msg, array $keywords, $baseStyle)
  {
    $k = implode ('|', $keywords);
    return "<span class=$baseStyle>" . preg_replace ("#\\b($k)\\b#", '<span class=keyword>$1</span>', $msg) . '</span>';
  }

  public static function getLibraryNamespace ()
  {
    $c = explode ('\\', get_class ());
    array_pop ($c);
    return implode ('\\', $c);
  }

  private static function render ()
  {
    global $application;

    if ($application->debugMode && strpos ($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
      WebConsoleRenderer::renderStyles ();
      WebConsoleRenderer::renderScripts ();
      WebConsoleRenderer::renderConsole (self::$panels);
    }
  }

}
