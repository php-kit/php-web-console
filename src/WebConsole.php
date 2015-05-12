<?php
namespace Impactwave\WebConsole;

/*
 * Provides the display of debugging information on a panel on the bottom
 * of the browser window.
 */
use Exception;
use Impactwave\WebConsole\Renderers\WebConsoleRenderer;
use Psr\Http\Message\ResponseInterface;

/**
 * @method static log(...$args) Alias of WebConsole::panel('log', ...$args).
 * @param mixed ...$args
 * @returns ConsolePanel
 */
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

  /**
   * Allows writing to a specific panel using an abbreviated syntax.
   *
   * ##### Ex:
   *
   *     WebConsole::log ("some text");
   *     WebConsole::log ()->write ("some text");
   *
   * Both will log to the 'log' panel.
   *
   * ##### Ex:
   *
   *     WebConsole::request ("text");
   *     WebConsole::request ()->write ("text");
   *
   * Both will write text to the 'request' panel.
   *
   * ##### Note:
   *
   * The calls are chainable, so you may invoke more methods os the same panel.
   *
   * @param string $name
   * @param array $args
   * @return ConsolePanel
   * @throws Exception
   */
  public static function __callStatic ($name, $args)
  {
    $panel = self::panel ($name);
    call_user_func_array ([$panel, 'log'], $args);
    return $panel;
  }

  static function init ($debugMode = true)
  {
    self::$class     = get_class ();
    self::$debugMode = $debugMode;
    self::registerPanel ('log', new ConsolePanel ('Inspector', 'fa fa-search'));
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
   * PSR-7-compatible version of {@see outputContent()}.
   *
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

  /**
   * Returns a ConsolePanel instance by name.
   * @param string $panelId
   * @return ConsolePanel
   * @throws Exception When the id is invalid.
   */
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
