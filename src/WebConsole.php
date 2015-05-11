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

  private static $debugMode;

  /**
   * Map of panel names (identifiers) to Console subclass instances.
   * @var ConsolePanel[]
   */
  private static $panels = [];

  static function init ($debugMode = true)
  {
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
    else error_log (self::panel('console')->render());
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
    else error_log (self::panel('console')->render());
    return $response;
  }

  public static function throwErrorWithLog (Exception $e)
  {
    $class              = get_class ($e);
    $openLogPaneMessage =
      "<p><a href='javascript:void(0)' onclick='document.getElementById(&quot;__console&quot;).style.height=&quot;auto&quot;'>Open the log pane</a> to see more details.";
    throw new $class($e->getMessage () . $openLogPaneMessage, $e->getCode (), $e);
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

  public function panel ($panelId)
  {
    if (isset(self::$panels[$panelId]))
      return self::$panels[$panelId];
    throw new Exception ("Invalid panel id: $panelId");
  }

  public function write ($msg)
  {
    self::panel ('console')->write ($msg);
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   * Params: list of one or more values to be displayed.
   * @return void
   */
  public function debug ()
  {
    call_user_func_array ([self::panel ('console'), 'debug'],  func_get_args ());
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   * Params: a title followed by a list of one or more values to be displayed.
   * @return void
   */
  public function debugSection ()
  {
    call_user_func_array ([self::panel ('console'), 'debugSection'], func_get_args ());
  }

  public function logSection ()
  {
    call_user_func_array ([self::panel ('console'), 'logSection'], func_get_args ());
  }

  public function log ()
  {
    call_user_func_array ([self::panel ('console'), 'log'], func_get_args ());
  }

}
