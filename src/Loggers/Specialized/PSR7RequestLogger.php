<?php
namespace PhpKit\WebConsole\Loggers\Specialized;

use PhpKit\WebConsole\Loggers\ConsoleLogger;
use Psr\Http\Message\ServerRequestInterface;

class PSR7RequestLogger extends ConsoleLogger
{
  /** @var ServerRequestInterface */
  public $request;

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
        'uploadedFiles'   => $r->getUploadedFiles (),
      ];
      return $this->getRenderedInspection ($r, $data);
    }
    return $this->table ($_GET, 'Request') . $this->table ($_SERVER, 'Server variables');
  }

  function setRequest (ServerRequestInterface $request)
  {
    $this->request = $request;
  }

}
