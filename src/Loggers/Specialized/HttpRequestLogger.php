<?php
namespace PhpKit\WebConsole\Loggers\Specialized;

use PhpKit\WebConsole\Loggers\ConsoleLogger;

class HttpRequestLogger extends ConsoleLogger
{
  function __construct ($title = 'Request', $icon = '')
  {
    parent::__construct ($title, $icon);
  }

  public function render ()
  {
    return
      $this->table ($_REQUEST, 'Request') .
      $this->table ($_SERVER, 'Server Variables');
  }

}
