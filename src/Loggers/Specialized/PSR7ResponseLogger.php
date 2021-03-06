<?php
namespace PhpKit\WebConsole\Loggers\Specialized;

use PhpKit\WebConsole\Loggers\ConsoleLogger;
use Psr\Http\Message\ResponseInterface;

class PSR7ResponseLogger extends ConsoleLogger
{
  /** @var ResponseInterface */
  public $response;

  public function render ()
  {
    if (isset($this->response)) {
      $body = $this->response->getBody ();
      try {
        $body->rewind ();
        $content = $body->getContents ();
        $body->rewind ();
      } catch (\Exception $e) {
      }
      if (isset($this->response)) {
        $r    = $this->response;
        $data = (object)[
          'statusCode'      => $r->getStatusCode (),
          'reasonPhrase'    => $r->getReasonPhrase (),
          'protocolVersion' => $r->getProtocolVersion (),
          'headers'         => $r->getHeaders (),
          'size'            => isset($content) ? friendlySize (strlen ($content), 3) : 'unknown',
        ];
        return $this->getRenderedInspection ($r, $data);
      }
    }
    return '';
  }

  function setResponse (ResponseInterface $response)
  {
    $this->response = $response;
  }

}
