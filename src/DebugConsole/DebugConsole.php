<?php
namespace PhpKit\WebConsole\DebugConsole;

/*
 * Provides the display of debugging information on a panel on the bottom
 * of the browser window.
 */
use Exception;
use PhpKit\WebConsole\DebugConsole\Renderers\DebugConsoleRenderer;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DebugConsole
{
  const DEFAULT_LOGGER_ID = 'main';
  static $TABLE_COLLAPSE_DEPTH = 4;
  static $TABLE_INDEX_WIDTH    = 50;
  static $TABLE_MAX_DEPTH      = 5;
  static $TABLE_PROP_WIDTH     = 170;
  static $TABLE_TYPE_WIDTH     = 100;

  /**
   * @var string For compatibility with PHP<5.5
   */
  static $class = __CLASS__;
  /**
   * Is WebConsole available?
   * @var bool
   */
  static $initialized = false;

  private static $debugMode;
  /**
   * Map of panel names (identifiers) to Console subclass instances.
   * @var ConsoleLogger[]
   */
  private static $loggers = [];

  /**
   * Gets the default logger instance.
   * If one doesn't exist yet, it creates it.
   * @return ConsoleLogger
   */
  public static function defaultLogger ()
  {
    $loggerId = self::DEFAULT_LOGGER_ID;
    if (isset(self::$loggers[$loggerId]))
      return self::$loggers[$loggerId];
    else return self::registerPanel ($loggerId, new ConsoleLogger());
  }

  /**
   * Checks if a ConsolePanel with the given name is registered.
   * @param string $panelId
   * @return bool
   */
  public static function hasLogger ($panelId)
  {
    return isset(self::$loggers[$panelId]);
  }

  public static function highlight ($msg, array $keywords, $baseStyle)
  {
    $k = implode ('|', $keywords);
    return "<span class=$baseStyle>" . preg_replace ("#\\b($k)\\b#", '<span class=keyword>$1</span>', $msg) . '</span>';
  }

  static function init ($debugMode = true, $defaultPanelTitle = 'Inspector', $defaultPanelIcon = 'fa fa-search')
  {
    self::$initialized = true;
    self::$debugMode   = $debugMode;
    self::registerPanel (self::DEFAULT_LOGGER_ID, new ConsoleLogger ($defaultPanelTitle, $defaultPanelIcon));
  }

  public static function libraryNamespace ()
  {
    $c = explode ('\\', get_class ());
    array_pop ($c);
    return implode ('\\', $c);
  }

  /**
   * Returns a ConsolePanel instance by name.
   * @param string $loggerId
   * @return ConsoleLogger
   * @throws Exception When the id is invalid.
   */
  public static function logger ($loggerId)
  {
    if (isset(self::$loggers[$loggerId]))
      return self::$loggers[$loggerId];
    throw new Exception ("Invalid panel id: <b>" . htmlentities ($loggerId) . '</b>');
  }

  /**
   * Returns an object's unique identifier (a short version), useful for debugging.
   * @param object $o
   * @return string
   */
  static function objectId ($o)
  {
    return substr (spl_object_hash ($o), 8, 8);
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
    if (self::$debugMode && strpos (get ($_SERVER, 'HTTP_ACCEPT'), 'text/html') !== false) {
      $content = ob_get_clean ();
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      // Note: if no <body> is found, the console will not be output.
      $out = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      echo !$count && $force ? $out . $myContent : $out;
    }
    else error_log (self::logger ('log')->render ());
  }

  /**
   * PSR-7-compatible version of {@see outputContent()}.
   *
   * Renders the console and inserts its content into the server response, if applicable.
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response Optional HTTP response object if the host application is PSR-7 compliant.
   * @param bool                   $force    Output console even if no body tag is found.
   * @return null|ResponseInterface The modified response, or NULL if the $response argument was not given.
   * @throws Exception
   */
  public static function outputContentViaResponse (ServerRequestInterface $request, ResponseInterface $response,
                                                   $force = false)
  {
    if (self::$debugMode) {
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      if ($response) {
        $contentType = $request->getHeaderLine ('Accept');
        if (strpos ($contentType, 'text/html') !== false) {
          $body    = $response->getBody ();
          $content = $body->__toString ();
          $content = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
          if (!$count && $force)
            $content .= $myContent;
          try {
            $body->rewind ();
          } catch (Exception $e) {
          }
          $body->write ($content);
          return $response->withHeader ('Content-Length', strval (strlen ($content)));
        }
        return $response;
      }
      $content = ob_get_clean ();
      // Note: if no <body> is found, the console will not be output.
      echo preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      return null;
    }
    else error_log (self::logger ('console')->render ());
    return $response;
  }

  public static function registerLogger ($loggerId, ConsoleLogger $logger)
  {
    self::$loggers[$loggerId] = $logger;
  }

  /**
   * @param string        $loggerId
   * @param ConsoleLogger $logger
   * @return ConsoleLogger
   */
  public static function registerPanel ($loggerId, ConsoleLogger $logger)
  {
    $logger->hasPanel         = true;
    self::$loggers[$loggerId] = $logger;
    return $logger;
  }

  public static function throwErrorWithLog (Exception $e)
  {
    $class              = get_class ($e);
    $openLogPaneMessage =
      "<p><a href='javascript:void(0)' onclick='openConsoleTab(\"database\")'>Open the log pane</a> to see more details.";
    throw new $class($e->getMessage () . $openLogPaneMessage, 0, $e);
  }

  /**
   * @return array List of loogers that have a visible panel.
   */
  private static function getPanels ()
  {
    return array_filter (self::$loggers, function (ConsoleLogger $i) { return $i->hasPanel; });
  }

  private static function render ()
  {
    if (self::$debugMode && strpos ($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
      DebugConsoleRenderer::renderStyles ();
      DebugConsoleRenderer::renderScripts ();
      DebugConsoleRenderer::renderConsole (self::getPanels ());
    }
  }

}
