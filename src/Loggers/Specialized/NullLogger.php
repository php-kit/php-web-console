<?php
namespace PhpKit\WebConsole\Loggers\Specialized;

use PhpKit\WebConsole\Loggers\ConsoleLogger;

/**
 * A logger that outputs nothing.
 *
 * <p>Instances of this logger are returned when you access a non-existing panel.
 */
class NullLogger extends ConsoleLogger
{
  public function write ($msg)
  {
    // NO OP
    return $this;
  }

}
