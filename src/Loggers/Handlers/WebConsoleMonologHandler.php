<?php
namespace PhpKit\WebConsole\Loggers\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;

class WebConsoleMonologHandler extends AbstractProcessingHandler
{
  /**
   * @param bool|int $level  The minimum logging level at which this handler will be triggered
   * @param Boolean  $bubble Whether the messages that are handled can bubble up the stack or not
   */
  public function __construct ($level = Logger::DEBUG, $bubble = true)
  {
    parent::__construct ($level, $bubble);
  }

  /**
   * Writes the record down to the log of the implementing handler
   *
   * @param  array $record
   * @return void
   */
  protected function write (array $record)
  {
    $channel  = $record['channel'];
    $loggerId = DebugConsole::hasLogger ($channel) ? $channel : DebugConsole::$settings->defaultLoggerId;
    $logger   = DebugConsole::logger ($loggerId);
    $logger->log ($record['level'], $record['message'], $record['context']);
  }
}
