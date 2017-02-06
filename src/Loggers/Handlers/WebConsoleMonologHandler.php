<?php
namespace PhpKit\WebConsole\Loggers\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;

class WebConsoleMonologHandler extends AbstractProcessingHandler
{
  public function __construct ($level = Logger::DEBUG, $bubble = true)
  {
    parent::__construct ($level, $bubble);
  }

  protected function write (array $record)
  {
    if (DebugConsole::$initialized) {
      $channel  = $record['channel'];
      $loggerId = DebugConsole::hasLogger ($channel) ? $channel : DebugConsole::$settings->defaultLoggerId;
      $logger   = DebugConsole::logger ($loggerId);
      $logger->log ($record['level'], $record['message'], $record['context']);
    }
  }

}
