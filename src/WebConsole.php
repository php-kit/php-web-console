<?php
namespace PhpKit\WebConsole;

/*
 * Provides the display of debugging information on a panel on the bottom
 * of the browser window.
 */
use Exception;
use PhpKit\WebConsole\Renderers\WebConsoleRenderer;
use Psr\Http\Message\ResponseInterface;

/**
 * @method static log(...$args) Alias of WebConsole::panel('log', ...$args).
 * @param mixed ...$args
 * @returns ConsolePanel
 */
class WebConsole
{
  static $TABLE_MAX_DEPTH      = 5;
  static $TABLE_COLLAPSE_DEPTH = 4;
  static $TABLE_PROP_WIDTH     = 170;
  static $TABLE_INDEX_WIDTH    = 50;
  static $TABLE_TYPE_WIDTH     = 100;

  static $class = __CLASS__;
  /**
   * Is WebConsole available?
   * @var bool
   */
  static $initialized = false;

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
    if (!self::$initialized)
      throw new \Exception ("You can't use the Web Console before it is initialized.");
    $panel = self::panel ($name);
    call_user_func_array ([$panel, 'log'], $args);
    return $panel;
  }

  static function init ($debugMode = true)
  {
    self::$initialized = true;
    self::$debugMode = $debugMode;
    self::registerPanel ('log', new ConsolePanel ('Inspector', 'fa fa-search'));
  }

  public static function registerPanel ($panelId, ConsolePanel $panel)
  {
    self::$panels[$panelId] = $panel;
  }

  /**
   * Renders the console and inserts its content into the server response, if applicable.
   * @param bool $force Output console even if no body tag is found.
   * @throws Exception
   */
  public static function outputContent ($force = false)
  {
    if (!self::$initialized)
      return;
    if (self::$debugMode && strpos (get($_SERVER, 'HTTP_ACCEPT'), 'text/html') !== false) {
      $content = ob_get_clean ();
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      // Note: if no <body> is found, the console will not be output.
      $out = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      echo !$count && $force ? $out . $myContent : $out;
    }
    else error_log (self::panel ('log')->render ());
  }

  /**
   * PSR-7-compatible version of {@see outputContent()}.
   *
   * Renders the console and inserts its content into the server response, if applicable.
   * @param ResponseInterface $response Optional HTTP response object if the host application is PSR-7 compliant.
   * @param bool $force Output console even if no body tag is found.
   * @return ResponseInterface|null The modified response, or NULL if the $response argument was not given.
   */
  public static function outputContentViaResponse (ResponseInterface $response, $force = false)
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
          if (!$count && $force)
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
      // Note: if no <body> is found, the console will not be output.
      echo preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      return null;
    }
    else error_log (self::panel ('console')->render ());
    return $response;
  }

  public static function throwErrorWithLog (Exception $e)
  {
    $class              = get_class ($e);
    $openLogPaneMessage =
      "<p><a href='javascript:void(0)' onclick='openConsoleTab(\"database\")'>Open the log pane</a> to see more details.";
    throw new $class($e->getMessage () . $openLogPaneMessage, 0, $e);
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
    if (self::$debugMode && strpos ($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
      WebConsoleRenderer::renderStyles ();
      WebConsoleRenderer::renderScripts ();
      WebConsoleRenderer::renderConsole (self::$panels);
    }
  }

}
