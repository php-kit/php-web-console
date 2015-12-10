<?php
namespace {
  use PhpKit\WebConsole\DebugConsole\DebugConsole;
  use PhpKit\WebConsole\Loggers\ConsoleLogger;

  /**
   * Displays a formatted representation of the given arguments to the default panel on the Debug Console.
   * <p>This is a shortcut for easing debugging.
   * @return ConsoleLogger
   */
  function inspect () //Note: if you rename this function, you must change ConsoleLogger::GLOBAL_LOG_FN too
  {
    $args   = array_merge (['<#log><#i>'], func_get_args ());
    $logger = DebugConsole::defaultLogger ();
    return call_user_func_array ([$logger, 'inspect'], $args)->showCallLocation ()->inspect ('</#i></#log>');
  }

}
namespace PhpKit\WebConsole\DebugConsole {

  /*
   * Provides the display of debugging information on a panel on the bottom
   * of the browser window.
   */
  use Exception;
  use PhpKit\WebConsole\DebugConsole\Renderers\DebugConsoleRenderer;
  use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
  use PhpKit\WebConsole\Loggers\ConsoleLogger;
  use Psr\Http\Message\ResponseInterface;
  use Psr\Http\Message\ServerRequestInterface;

  class DebugConsole
  {
    /**
     * @var string For compatibility with PHP<5.5
     */
    static $class = __CLASS__;
    /**
     * Is WebConsole available?
     * @var bool
     */
    static $initialized = false;
    /**
     * @var DebugConsoleSettings
     */
    static         $settings;
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
      $loggerId = self::$settings->defaultLoggerId;
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
      return "<span class=$baseStyle>" . preg_replace ("#\\b($k)\\b#", '<span class=keyword>$1</span>', $msg) .
             '</span>';
    }

    static function init ($debugMode = true, DebugConsoleSettings $settings = null)
    {
      self::$settings    = $settings ?: new DebugConsoleSettings;
      self::$initialized = true;
      self::$debugMode   = $debugMode;
      self::registerPanel (self::$settings->defaultLoggerId,
        new ConsoleLogger ($settings->defaultPanelTitle, $settings->defaultPanelIcon));
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
     * Renders the console and inserts its content into the server response, if applicable.
     *
     * Only GET requests with an `Accept: text/html` header are accepted.
     * Otherwise, it does nothing and the currently buffered output is kept.
     *
     * @param bool $force Output console even if no body tag is found.
     * @throws Exception
     */
    public static function outputContent ($force = false)
    {
      if (!self::$initialized
          || !self::$debugMode
          || get ($_SERVER, 'REQUEST_METHOD') != 'GET'
          || strpos (get ($_SERVER, 'HTTP_ACCEPT'), 'text/html') === false
      ) return;

      $content = ob_get_clean ();
      ob_start ();
      self::render ();
      $myContent = ob_get_clean ();
      // Note: if no <body> is found, the console will not be output.
      $out = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
      echo !$count && $force ? $out . $myContent : $out;
    }

    /**
     * PSR-7-compatible version of {@see outputContent()}.
     *
     * Renders the console and inserts its content into the server response, if applicable.
     * Only GET requests with an `Accept: text/html` header are transformed. All other output is kept untouched.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface|null $response Optional HTTP response object if the host application is PSR-7 compliant.
     * @param bool                   $force    Output console even if no body tag is found.
     * @return null|ResponseInterface The modified response, or NULL if the $response argument was not given.
     * @throws Exception
     */
    public static function outputContentViaResponse (ServerRequestInterface $request,
                                                     ResponseInterface $response = null,
                                                     $force = false)
    {
      if (self::$debugMode) {
        ob_start ();
        self::render ();
        $myContent = ob_get_clean ();

        if ($response) {
          if ($request->getMethod () == 'GET') {
            $contentType = $request->getHeaderLine ('Accept');
            if (strpos ($contentType, 'text/html') !== false) {
              $body    = $response->getBody ();
              $content = $body->__toString ();
              $content = preg_replace ('#(</body>\s*</html>\s*)$#i', "$myContent\$1", $content, -1, $count);
              if (!$count && $force)
                $content .= $myContent;
              try {
                $body->rewind ();
              }
              catch (Exception $e) {
                // suppress exceptions
              }
              $body->write ($content);
              return $response->withHeader ('Content-Length', strval (strlen ($content)));
            }
          }
          return $response;
        }

        else self::outputContent ($force);
      }
      return $response;
    }

    public static function registerLogger ($loggerId, ConsoleLogger $logger)
    {
      return self::$loggers[$loggerId] = $logger;
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
     * Outputs a stack trace up to the first call to a trace function (which may be this one or a wrapper that calls
     * this one).
     * <p>It displays detailed timing and memory consumption information about each function/method call.
     *
     * <p>It requires a logger panel named 'trace' to be defined.
     * <p>It also requires XDebug to be installed.
     *
     * ##### Usage
     *
     * Put the following code at the place where you want the trace log to be captured:
     *
     *       \PhpKit\WebConsole\DebugConsole\DebugConsole::trace ();
     *
     * @return string
     */
    public static function trace ()
    {
      $v = ini_get ('xdebug.collect_params');
      ob_start ();
      ini_set ('xdebug.collect_params', 1);
      xdebug_print_function_stack ();
      $trace = ob_get_clean ();
      $trace = preg_replace ('@^(?:.*?)<table class=\'xdebug-error xe-xdebug\'(.*?)<tr>(?:.*?)>Location</th></tr>@s',
        '<table class="__console-table trace"$1<colgroup>
      <col width=40><col width=72><col width=72><col width=75%><col width=25%>
      <thead><tr><th>#<th>Time (ms)<th>Mem.(MB)<th>Function<th>Location</tr></thead>', $trace);
      $trace = preg_replace (
        ['@</table>.*@s', "/align='center'/", '@(trace\(  \)</td>.*?</tr>)(.*)</table>@s'],
        ['</table>', 'align=right', '$1</table>'],
        $trace);
      $trace = preg_replace_callback (
        '#<tr><td (.*?)>(.*?)</td><td (.*?)>(.*?)</td><td (.*?)>(.*?)</td><td (.*?)>(.*?)</td><td title=\'(.*?)\'(.*?)>(.*?)</td></tr>#',
        function ($m) {
          $t = number_format ($m[4] * 1000, 1);
          $r = number_format ($m[6] / 1048576, 3);
          $p = ErrorConsole::shortFileName ($m[9]);
          $f = substr ($m[11], 3);
          return "<tr><th $m[1]>$m[2]<td $m[3]>$t<td $m[5]>$r<td $m[7]>$m[8]<td class='__type' title='$p'$m[10]>$f</tr>";
        }, $trace);
      ini_set ('xdebug.collect_params', $v);
      self::logger ('trace')->write ($trace);
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

}
