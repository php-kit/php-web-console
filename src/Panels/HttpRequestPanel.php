<?php
namespace Impactwave\WebConsole\Panels;

use Impactwave\WebConsole\ConsolePanel;

class HttpRequestPanel extends ConsolePanel
{
  function __construct ($title = 'Request', $icon = '')
  {
    parent::__construct ($title, $icon);
  }

  public function render ()
  {
    return $this->table ($_GET, 'Request') . $this->table ($_SERVER, 'Server variables');
  }

}