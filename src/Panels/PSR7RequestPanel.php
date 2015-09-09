<?php
namespace PhpKit\WebConsole\Panels;

use PhpKit\WebConsole\ConsolePanel;
use Psr\Http\Message\ServerRequestInterface;

class PSR7RequestPanel extends ConsolePanel
{
  /** @var ServerRequestInterface */
  public $request;

  function __construct (ServerRequestInterface $request, $title = 'Request', $icon = '')
  {
    parent::__construct ($title, $icon);
    $this->request = $request;
  }

  public function render ()
  {
    if (isset($this->request)) {
      $r    = $this->request;
      $data = (object)[
        'requestTarget'   => $r->getRequestTarget (),
        'method'          => $r->getMethod (),
        'protocolVersion' => $r->getProtocolVersion (),
        'headers'         => $r->getHeaders (),
        'cookieParams'    => $r->getCookieParams (),
        'queryParams'     => $r->getQueryParams (),
        'parsedBody'      => $r->getParsedBody (),
        'attributes'      => $r->getAttributes (),
        'serverParams'    => $r->getServerParams (),
        'uploadedFiles'   => $r->getUploadedFiles ()
      ];
      return $this->table ($data);
    }
    return $this->table ($_GET, 'Request') . $this->table ($_SERVER, 'Server variables');
  }

}
