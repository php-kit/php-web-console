<?php
namespace PhpKit\WebConsole\Panels;

use PhpKit\WebConsole\ConsolePanel;

class HttpRequestPanel extends ConsolePanel
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
