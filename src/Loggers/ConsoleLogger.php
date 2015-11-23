<?php
namespace PhpKit\WebConsole\Loggers;

use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
  /**
   * Logging levels from syslog protocol defined in RFC 5424
   *
   * @var array $LEVELS Logging levels
   */
  protected static $LEVELS = [
    100 => 'DEBUG',
    200 => 'INFO',
    250 => 'NOTICE',
    300 => 'WARNING',
    400 => 'ERROR',
    500 => 'CRITICAL',
    550 => 'ALERT',
    600 => 'EMERGENCY',
  ];

  public $hasPanel = false;
  public $icon;
  public $title;
  /**
   * The caption to be used on the next table.
   * @var string
   */
  protected $caption;
  /**
   * @var string The panel's HTML content.
   */
  protected $content = '';
  /**
   * Filter callback for tables.
   *
   * <p>When set, for each object/array being debugged, the callbak receives the key name, the value and the target
   * object/array.
   * <p>It should return `true` if the value should be displayed.
   * @var callable
   */
  protected $filter;

  function __construct ($title = 'Logger', $icon = '')
  {
    $this->title = $title;
    $this->icon  = $icon;
  }

  /**
   * @param mixed $v
   * @return string
   */
  static function getType ($v)
  {
    if (is_object ($v)) {
      $c = get_class ($v);
      return self::shortenType ($c);
    }
    if (is_array ($v))
      return 'array(' . count (array_keys ($v)) . ')';

    return gettype ($v);
  }

  /**
   * @param string $c
   * @return string
   */
  static function shortenType ($c)
  {
    $l = array_slice (explode ('\\', $c), -1)[0];
    return "<span title='$c'>$l</span>";
  }

  /**
   * Interpolates context values into message placeholders, for use on PSR-3-compatible logging.
   *
   * @param string $message Message with optional placeholder with syntax {key}.
   * @param array  $context Array from where to fetch values corresponing to the interpolated keys.
   * @return string
   */
  private static function interpolate ($message, array $context = [])
  {
    // build a replacement array with braces around the context keys
    $replace = [];
    foreach ($context as $key => $val) {
      $replace['{' . $key . '}'] = $val;
    }
    // interpolate replacement values into the message and return
    return strtr ($message, $replace);
  }

  function getContent ()
  {
    $c             = $this->content;
    $this->content = '';
    return $c;
  }

  function getRenderedInspection ($val, $alt = null)
  {
    return $this->format ($this->getInspection1 ($val, $alt));
  }

  /**
   * Displays detailed information about each specified value.
   *
   * @param mixed $...args Each value to be inspected and displayed on an horizontal sequence.
   * @return $this
   */
  public function inspect ()
  {
    foreach (func_get_args () as $arg)
      $this->write ($this->getInspection1 ($arg));
    return $this;
  }

  /**
   * Displays detailed information about the specified value.
   * @param mixed $val
   * @param mixed $alt
   * @return $this
   */
  function inspectValue ($val, $alt = null)
  {
    return $this->write ($this->getRenderedInspection ($val, $alt));
  }

  /**
   * PSR-3-compliant logging method.
   *
   * @param mixed  $level
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public function log ($level, $message, array $context = [])
  {
    $levelName = isset(self::$LEVELS[$level]) ? self::$LEVELS[$level] : $level;
    $message   = self::interpolate ($message, $context);
    $this->write ("<#log><#i><span class=__alert>$levelName</span> $message</#i></#log>");
  }

  /**
   * Returns an object's unique identifier (a short version), useful for debugging.
   * @param object $o
   * @return $this
   */
  function objectId ($o)
  {
    return $this->write (substr (spl_object_hash ($o), 8, 8));
  }

  public function render ()
  {
    return $this->format ($this->content);
  }

  public function showCallLocation ()
  {
    $namespace = DebugConsole::libraryNamespace ();
    $base      = __DIR__;
    $stack     = debug_backtrace (0);
    // Discard frames of all functions that belong to this library.
    while (!empty($stack) && (
        (isset($stack[0]['file']) && stripos ($stack[0]['file'], $base) === 0) ||
        (isset($stack[0]['class']) && stripos ($stack[0]['class'], $namespace) === 0)
      )
    ) array_shift ($stack);
    $trace     = $stack[0];
    $path      = isset($trace['file']) ? $trace['file'] : '';
    $line      = isset($trace['line']) ? $trace['line'] : '';
    $shortPath = ErrorConsole::shortFileName ($path);
    $location  = empty($line) ? $shortPath : ErrorConsole::errorLink ($path, $line, 1, "$shortPath($line)");
    if ($path != '')
      $path = <<<HTML
<div class="__debug-location"><b>At</b> $location</div>
HTML;
    $this->write ($path);

    return $this;
  }

  function simpleTable ($data, $title = '', $columnHeaders = false)
  {
    $this->write ($this->table ($data, $title, 0, false, $columnHeaders));
    return $this;
  }

  function typeName ($v)
  {
    return $this->write ('<span class="__type">' . self::getType ($v) . '</span>');
  }

  function withCaption ($caption)
  {
    $args          = array_slice (func_get_args (), 1);
    $this->caption = $caption;
    call_user_func_array ([$this, 'inspect'], $args);

    return $this;
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   *
   * > The filter function may remove some keys from the tabular output of objects or arrays.
   *
   * Extra params: list of one or more values to be displayed.
   * @param callable $fn Filter callback. Receives the key name, the value and the target object/array.<br>
   *                     Returns <kbd>true</kbd> if the value should be displayed, <kbd>false</kbd> if the whole
   *                     key + value should be hidden, or <kbd>'...'</kbd> if the key should appear, but the value
   *                     be omitted.
   * @return $this
   */
  public function withFilter (callable $fn)
  {
    $args         = array_slice (func_get_args (), 1);
    $this->filter = $fn;
    call_user_func_array ([$this, 'inspect'], $args);
    $this->filter = null;

    return $this;
  }

  public function write ($msg)
  {
    $this->content .= $msg;

    return $this;
  }

  /**
   * Renders log markup to HTML.
   *
   * ##### Syntax:
   * ```
   *    <#tag>text</#tag>
   *    <#tag|arg1|...argN>text</#tag>
   * ```
   * ##### Supported tags:
   * - `<#t>text</#t>` - non-evaluated raw HTML text.
   * - `<#i>text</#i>` - Log item. It adds spacing.
   * - `<#section|title>text</#section>` - A section box with an optional title.
   * - `<#log>content</#log>` - Output content wrapped by a log stripe.
   * - `<#data>data</#data>` - Format a data structure's textual representation.
   * - `<#header>text</#header>` - A subsection title.
   * - `<#footer>text</#footer>` - Extra information, right aligned.
   * - `<#alert>text</#alert>` - Warning message.
   *
   * @param $msg
   * @return mixed
   */
  protected function format ($msg)
  {
    if (is_string ($msg)) {
      do {
        $msg = preg_replace_callback ('~<#(.+?)(?:\|([^>]+))?>(.*?)</#\1>~s', function ($m) {
          list ($all, $tag, $args, $str) = $m;
          $args = $args ? explode ('|', $args) : [];
          switch ($tag) {
            case 'i':
              return "<div class='__log-item " . get ($args, 0) . "'>$str</div>";
            case 'section':
              return "<div class='__log-section'>" . ($args ? "<div class='__log-title'>$args[0]</div>" : '') .
                     "$str</div>";
            case 'log':
              return "<div class='__log-stripe'>$str</div>";
            case 'data':
              return "<div class='__log-data'>$str</div>";
            case 'header':
              return "<div class='__header'>$str</div>";
            case 'footer':
              return "<div class='__footer'>$str</div><div></div>";
            case 'alert':
              return "<div class='__alert'>$str</div>";
            case 'type':
              $type = self::shortenType ($str);
              return "<span class='__type'>$type</span>";
            case 'indent':
              return "<div class='indent'>$str</div>";
            default:
              ob_clean ();
              throw new \RuntimeException("Invalid log tag <#$tag>");
          }
        }, $msg, -1, $count);
      } while ($count);
    }

    return $msg;
  }

  protected function formatType ($val, $expand = '')
  {
    if (is_object ($val))
      $id = ' <small>#' . DebugConsole::objectId ($val) . '</small>';
    else $id = '';
    return "<#header>Type: <span class='__type'>" . self::getType ($val) . "</span>$id</#header>$expand";
  }

  protected function table ($data, $title = '', $depth = 0, $typeColumn = true, $columnHeaders = true)
  {
    if ($this->caption) {
      $title         = $this->caption;
      $this->caption = '';
    }

    // DISPLAY PRIMITIVE VALUES

    if ($data instanceof \Closure) {
      return '<i>(native code)</i>';
    }
    elseif (is_bool ($data))
      return $data ? 'true' : 'false';
    elseif (!is_array ($data) && !is_object ($data)) {
      return htmlspecialchars (str_replace ('    ', '  ', trim (print_r ($data, true))));
    }

    // SETUP TABULAR DISPLAY OF ARRAYS AND OBJECTS

    $w1 = DebugConsole::$settings->tablePropertyColumnWidth;
    $c1 = '';

    if (is_array ($data)) {
      if ($depth == DebugConsole::$settings->tableMaxDepth)
        return '<i>(...)</i>';
      ++$depth;
      $label = 'Key';
      if (isset($data[0])) {
        $label = 'Index';
        $w1    = DebugConsole::$settings->tableIndexColumnWidth;
        $c1    = ' class="n"';
      }
    }
    elseif (is_object ($data)) {
      if ($depth == DebugConsole::$settings->tableMaxDepth)
        return '<i>(...)</i>';
      ++$depth;
      if (method_exists ($data, '__debugInfo'))
        $data = $data->__debugInfo ();
      else $data = get_object_vars ($data);
      if (empty($data))
        return '';
      $label = 'Property';
      uksort ($data, 'strnatcasecmp');
    }

    // DRAW TABLE

    $filter = isset($this->filter) ? $this->filter : function ($k) { return true; };
    ob_start (null, 0);
    if ($depth >= DebugConsole::$settings->tableCollapseDepth)
      echo '<div class="__expand"><a class="fa fa-plus-square" href="javascript:void(0)" onclick="this.parentNode.className+=\' show\'"></a>';
    ?>
  <table class="__console-table<?= $title ? ' with-caption' : '' ?>">
    <?= $title ? "<caption>$title</caption>"
    : '' ?><?php if (empty($data)) echo '<thead><tr><td colspan=3><i>empty</i>';
  else { ?>
    <colgroup>
      <col width="<?= $w1 ?>">
      <?php if ($typeColumn): ?>
        <col width="<?= DebugConsole::$settings->tableTypeColumnWidth ?>">
      <?php endif ?>
      <col width="100%">
    </colgroup>
    <?php if ($columnHeaders): ?>
      <thead>
      <tr>
        <th><?= $label ?></th>
        <?php if ($typeColumn): ?>
          <th>Type</th>
        <?php endif ?>
        <th>Value</th>
      </thead>
    <?php endif ?>
    <tbody>
    <?php
    foreach ($data as $k => $v):
    $x = $filter($k, $v, $data);
    if (!$x) continue;
    ?>
    <tr>
      <th<?= $c1 ?>><?= $k ?></th>
      <?php if ($typeColumn): ?>
        <td><?= self::getType ($v) ?></td>
      <?php endif ?>
      <td><?= $x === '...' ? '<i>ommited</i>' : $this->table ($v, '', $depth, $typeColumn, $columnHeaders) ?></td>
      <?php endforeach; ?>
    </tbody>
  <?php } ?>
    </table><?php
    if ($depth >= DebugConsole::$settings->tableCollapseDepth)
      echo '</div>';

    return trim (ob_get_clean ());
  }

  /**
   * @param mixed $val The value to be inspected.
   * @param mixed $alt If not null, a replacement for inspection; type information about $val is displayed by the
   *                   inspection is performed on this argument instead.
   * @return string
   */
  private function getInspection1 ($val, $alt = null)
  {
    if (!is_string ($val))
      $val = $this->getInspection2 ($val, $alt);
    elseif (substr ($val, 0, 2) == '<#') {
      if (substr ($val, 2, 2) == 't>') $val = substr ($val, 4, -5);
      // else passthrough
    }
    elseif (substr ($val, 0, 3) != '</#')
      $val = $this->getInspection2 ($val, $alt);
    return $val;
  }

  /**
   * @param mixed $val The value to be inspected.
   * @param mixed $alt If not null, a replacement for inspection; type information about $val is displayed by the
   *                   inspection is performed on this argument instead.
   * @return string
   */
  private function getInspection2 ($val, $alt = null)
  {
    $arg = $this->table (isset($alt) ? $alt : $val);
    if (is_scalar ($val) || is_null ($val)) {
      if (!strlen ($arg))
        $arg = is_null ($val) ? 'NULL' : "''";
      $arg = '<i>(' . self::getType ($val) . ")</i> $arg";

      return "<#data>$arg</#data>";
    }
    return $this->formatType ($val, $arg);
  }

}
