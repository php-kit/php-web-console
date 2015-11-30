<?php
namespace PhpKit\WebConsole\Loggers\Specialized;

use PhpKit\WebConsole\DebugConsole\Renderers\DebugConsoleRenderer;
use PhpKit\WebConsole\Loggers\ConsoleLogger;

/**
 * A logger that writes directly to the HTTP/terminal output stream.
 *
 * <p>Use it as an emergency debugging tool when your application crashes at a point where the console is not even
 * rendered.
 * > You should use a single logger of this type, as it grabs the full control of the output.
 * > If you use multiple instances of this logger, they'll all output to the same stream.
 */
class DirectOutputLogger extends ConsoleLogger
{
  static $ready = false;

  public function __construct ($title = '', $icon = '')
  {
    parent::__construct ($title, $icon);
    if (!self::$ready) {
      self::$ready = true;
      DebugConsoleRenderer::renderStyles ();
      echo "<style>body { font-family: sans-serif }</style>";
    }
  }

  public function write ($msg)
  {
    $msg = preg_replace ('/<\/?#.*?>/', '', $msg);
    echo $msg;
    return $this;
  }

}
