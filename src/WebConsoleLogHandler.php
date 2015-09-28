<?php
namespace PhpKit\WebConsole;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class WebConsoleLogHandler extends AbstractProcessingHandler
{
  /** @var ConsolePanel */
  protected $panel;

  /**
   * @param ConsolePanel $panel  Associate the logger with this output panel.
   * @param bool|int     $level  The minimum logging level at which this handler will be triggered
   * @param Boolean      $bubble Whether the messages that are handled can bubble up the stack or not
   */
  public function __construct (ConsolePanel $panel, $level = Logger::DEBUG, $bubble = true)
  {
    parent::__construct ($level, $bubble);
    $this->panel = $panel;
  }

  /**
   * Writes the record down to the log of the implementing handler
   *
   * @param  array $record
   * @return void
   */
  protected function write (array $record)
  {
    $this->panel->log ($record);
  }
}
