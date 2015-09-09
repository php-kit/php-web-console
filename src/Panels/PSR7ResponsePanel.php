<?php
namespace PhpKit\WebConsole\Panels;

use PhpKit\WebConsole\ConsolePanel;
use Psr\Http\Message\ResponseInterface;

class PSR7ResponsePanel extends ConsolePanel
{
  /** @var ResponseInterface */
  public $response;

  function __construct (ResponseInterface $response, $title = 'Response', $icon = '')
  {
    parent::__construct ($title, $icon);
    $this->response = $response;
  }

  public function render ()
  {
    if (isset($this->response)) {
      $r    = $this->response;
      $data = (object)[
        'statusCode'      => $r->getStatusCode (),
        'reasonPhrase'    => $r->getReasonPhrase (),
        'protocolVersion' => $r->getProtocolVersion (),
        'headers'         => $r->getHeaders (),
      ];
      return $this->table ($data);
    }
    return '';
  }

}
