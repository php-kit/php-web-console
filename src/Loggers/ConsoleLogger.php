<?php

namespace PhpKit\WebConsole\Loggers;

use Electro\Interfaces\CustomInspectionInterface;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
  /**
   * The name of the global logging functions, defined on DebugConsole.php
   */
  const GLOBAL_LOG_FNS = [
    'inspect' => 1,
    '_log'    => 1,
  ];
  /**
   * Logging levels from syslog protocol defined in RFC 5424
   *
   * @var array $LEVELS Logging levels
   */
  static protected $LEVELS = [
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
   *
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
   *
   * @var callable
   */
  protected $filter;

  function __construct ($title = 'Logger', $icon = '')
  {
    $this->title = $title;
    $this->icon  = $icon;
  }


  function getContent ()
  {
    $c             = $this->content;
    $this->content = '';
    return $c;
  }

  function getRenderedInspection ($val, $alt = null)
  {
    // Note: Electro's CustomInspectionInterface implements the inspect() method, but this library is not dependent on
    // any external interface.
    return isset ($alt) && $alt instanceof CustomInspectionInterface ? $val->inspect ()
      : $this->format ($this->getInspection1 ($val, $alt));
  }

  /**
   * @param mixed                  $data
   * @param string                 $title
   * @param bool                   $typeColumn
   * @param bool                   $columnHeaders
   * @param int                    $maxDepth
   * @param string[]|callable|null $excludeProps [optional]
   * @return string
   */
  function getTable ($data, $title = '', $typeColumn = false, $columnHeaders = false, $maxDepth = -1,
                     $excludeProps = null)
  {
    $prev = $this->filter;
    if ($excludeProps) {
      if (is_array ($excludeProps))
        $this->filter = function ($k, $v, $o) use ($excludeProps) {
          return !in_array ($k, $excludeProps);
        };
      else if (is_callable ($excludeProps))
        $this->filter = $excludeProps;
    }
    $x            = $this->table ($data, $title, 0, $typeColumn, $columnHeaders, $maxDepth);
    $this->filter = $prev;
    return $x;
  }

  function hasContent ()
  {
    return $this->content != '';
  }

  /**
   * Displays detailed information about each specified value.
   *
   * @param mixed $...args Each value to be inspected and displayed on an horizontal sequence.
   * @return $this
   */
  public function inspect ()
  {
    if (!DebugConsole::$settings)
      throw new \RuntimeException ("Web console not initialized");
    foreach (func_get_args () as $arg)
      $this->inspectValue ($arg);
    return $this;
  }

  /**
   * Displays detailed information about the specified value.
   *
   * @param mixed $val The value to be inspected.
   * @param mixed $alt If specified, this will be the value that is displayed in tabular format.
   * @return $this
   */
  function inspectValue ($val, $alt = null)
  {
    return $this->write ($this->getRenderedInspection ($val, $alt))->write (' ');
  }

  /**
   * @param mixed         $val The value to be inspected.
   * @param callable|null $fn  A filter callback.
   * @return string
   */
  function inspectWithNoTypeInfo ($val, callable $fn = null)
  {
    if ($fn)
      $this->filter = $fn;
    $this->write ($this->table ($val));
    $this->filter = null;
    return $this;
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
    $message   = Debug::interpolate ($message, $context);
    $this->write ("<#log><#i><span class=__alert>$levelName</span> $message</#i></#log>");
  }

  /**
   * Returns an object's unique identifier (a short version), useful for debugging.
   *
   * @param object $o
   * @return $this
   */
  function objectId ($o)
  {
    return $this->write (Debug::objectId ($o));
  }

  public function render ()
  {
    return $this->format ($this->content);
  }

  public function showCallLocation ()
  {
    $namespace = Debug::libraryNamespace ();
    $base      = __DIR__;
    $stack     = debug_backtrace (0);
    $FNS       = self::GLOBAL_LOG_FNS;
    // Discard frames of all functions that belong to this library.
    while (!empty($stack) && (
        (isset($stack[0]['file']) && stripos ($stack[0]['file'], $base) === 0) ||
        (isset($stack[0]['class']) && stripos ($stack[0]['class'], $namespace) === 0) ||
        (isset($stack[0]['function']) && !isset($FNS[$stack[0]['function']]))
      )
    ) array_shift ($stack);
    $trace     = $stack ? $stack[0] : [];
    $path      = isset($trace['file']) ? $trace['file'] : '';
    $line      = isset($trace['line']) ? $trace['line'] : '';
    $shortPath = ErrorConsole::shortFileName ($path);
    $shortPath = str_segmentsLast ($shortPath, '/');
    $location  = empty($line)
      ? $shortPath
      : ErrorConsole::errorLink ($path, $line, 1, "$shortPath:$line", 'hint--rounded hint--left',
        'data-hint');
    if ($path != '')
      $path = <<<HTML
<div class="__debug-location">At $location</div>
HTML;
    $this->write ($path);

    return $this;
  }

  function simpleTable ($data, $title = '', $typeColumn = false, $columnHeaders = false)
  {
    $this->write ($this->table ($data, $title, 0, $typeColumn, $columnHeaders));
    return $this;
  }

  function typeName ($v)
  {
    return $this->write ('<span class="__type">' . Debug::getType ($v) . '</span>');
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
   * <p>**Warning:** always use the `===` operator to compare keys with something, because if the key is the integer
   * 0, the operator `==` will return `true` when it shouldn't.
   *
   * Extra params: list of one or more values to be displayed.
   *
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

  /**
   * Writes text to the logger.
   *
   * ><p>This is the only output function that should be overriden when creating a specialized logger.
   *
   * @param string $msg
   * @return $this
   */
  public function write ($msg)
  {
    $this->content .= $msg;

    return $this;
  }

  public function writef ()
  {
    return $this->write (call_user_func_array ('sprintf', func_get_args ()));
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
   *
   * <table>
   * <tr><td><kbd> &lt;#t> text &lt;/#t></kbd>                    <td> non-evaluated raw HTML text.
   * <tr><td><kbd> &lt;#i|CSS classes> text &lt;/#i></kbd>        <td> Log item. It adds spacing.
   * <tr><td><kbd> &lt;#row|CSS classes> text &lt;/#wor></kbd>    <td> A line of information, similar to #i, but with an
   * automatically numbered row header column.
   * <tr><td><kbd> &lt;#section|title> text &lt;/#section></kbd>  <td> A section box with an optional title.
   * <tr><td><kbd> &lt;#log> content &lt;/#log></kbd>             <td> Output content wrapped by a log stripe.
   * <tr><td><kbd> &lt;#data> data &lt;/#data></kbd>              <td> Format a data structure's textual
   * representation.
   * <tr><td><kbd> &lt;#header> text &lt;/#header></kbd>          <td> A subsection title.
   * <tr><td><kbd> &lt;#footer> text &lt;/#footer></kbd>          <td> Extra information, right aligned.
   * <tr><td><kbd> &lt;#alert> text &lt;/#alert></kbd>            <td> Warning message.
   * <tr><td><kbd> &lt;#type> text &lt;/#type></kbd>              <td> A colored short type name with more details on a
   * tooltip.
   * <tr><td><kbd> &lt;#indent> text &lt;/#indent></kbd>          <td> An indented block.
   * </table>
   *
   * @param $msg
   * @return mixed
   */
  protected function format ($msg)
  {
    if (is_string ($msg)) {
      do {
        $msg = preg_replace_callback ('~
<\#(\w+)                            # capture LOG MARKUP TAG (ex: <#tag -> tag)
\s* (?: \| ([^>]+) )?               # capture optional tag arguments  (ex: <#tag|a|b|c> -> a|b|c)
>
(                                   # capture the tag\'s content
  (?:                               # begin loop
    (?= <\#\1 [ \s\|> ] )           # either the same tag is opened again (<#tag> <#tag| or <#tag(space))
    (?R)                            # and we must recurse
  |                                 # or
    .*?                             # capture everything
    (?= </\#\1> | <\#\1 [ \s\|> ] ) # until a closing/opening tag with the same name occurs
  )*                                # repeat loop
)                                   # end capture
</\#\1>                             # consume closing tag
~sx', function ($m) {
          list ($all, $tag, $args, $str) = $m;
          $args = $args ? explode ('|', $args) : [];
          switch ($tag) {
            case 'i':
              return "<div class='__log-item " . get ($args, 0) . "'>$str</div>";
            case 'row':
              return "<div class='__log-item __rowHeader " . get ($args, 0) . "'>$str</div>";
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
              return "<div class='__footer'>$str</div>";
            case 'alert':
              return "<div class='__alert'>$str</div>";
            case 'type':
              $type = Debug::shortenType ($str);
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
    return "<#header>Type: <span class='__type'>" . Debug::getType ($val) . "</span></#header>$expand";
  }

  protected function table ($data, $title = '', $depth = 0, $typeColumn = true, $columnHeaders = true, $maxDepth = -1)
  {
    $isList       = false;
    $originalData = $data;
    if ($this->caption) {
      $title         = $this->caption;
      $this->caption = '';
    }

    // DISPLAY PRIMITIVE VALUES

    if (!is_array ($data) && !is_object ($data) || is_null ($data) || $data instanceof \PowerString)
      return Debug::toString ($data);

    // SETUP TABULAR DISPLAY OF ARRAYS AND OBJECTS

    $w1 = DebugConsole::$settings->tablePropertyColumnWidth;
    $c1 = '';

    if (is_array ($data)) {
      if (!count ($data))
        return '<i>[]</i>';
      if ($depth == DebugConsole::$settings->tableMaxDepth || $depth == $maxDepth)
        return '<i>(...)</i>';
      ++$depth;
      $label = 'Key';
      if (isset($data[0])) {
        $isList = true;
        $label  = 'Index';
        $w1     = DebugConsole::$settings->tableIndexColumnWidth;
        $c1     = ' class="n"';
      }
    }
    elseif (is_object ($data)) {
      if ($depth == DebugConsole::$settings->tableMaxDepth || $depth == $maxDepth)
        return '<i>(...)</i>';
      ++$depth;
      // Note: Electro's CustomInspectionInterface implements the inspect() method, but this library is not dependent on
      // any external interface.
      if ($data instanceof CustomInspectionInterface)
        return $data->inspect ();
      elseif (method_exists ($data, '__debugInfo')) {
        $data = $data->__debugInfo ();
        if (empty($data))
          return '<i>(empty)</i>';
      }
      // Exclude generators because they can only be iterated once and we do not want to consume them.
      else if ((!$data instanceof \Generator) && $it = iteratorOf ($data, false))
        $data = iterator_to_array ($it);
      else {
        $data = get_object_vars ($data);
        if (empty($data))
          return '<i>(not inspectable)</i>';
      }
      if (!is_string ($data)) {
        $label = 'Property';
        //TODO: allow sorting keys only if the caller wants so.
        //uksort ($data, 'strnatcasecmp');
      }
    }

    // DRAW TABLE

    $filter = isset($this->filter) ? $this->filter : function () { return true; };
    ob_start (null, 0);
    if ($depth >= DebugConsole::$settings->tableCollapseDepth)
      echo '<div class="__expand"><a class="fa fa-plus-square" href="javascript:void(0)" onclick="this.parentNode.className+=\' show\'"></a>';

    if (is_string ($data))
      echo $data;
    else {
      ?>
    <table class="__console-table<?= $title ? ' with-caption' : '' ?>">
      <?= $title ? "<caption>$title</caption>"
      : '' ?><?php if (empty($data)) echo '<thead><tr><td colspan=3><i>[]</i>';
      else {
        if (DebugConsole::$settings->tableUseColumWidths) {
          ?>
          <colgroup>
            <col width="<?= $w1 ?>">
            <?php if ($typeColumn): ?>
              <col width="<?= DebugConsole::$settings->tableTypeColumnWidth ?>">
            <?php endif ?>
            <col width="100%">
          </colgroup>
          <?php
        }
        if ($columnHeaders): ?>
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
        $c = 0;
        foreach ($data as $k => $v):
        if ($isList && ++$c > DebugConsole::$settings->maxIndexedArrayItems) {
          echo '<tr><td><i>(...)</i>';
          break;
        }
        $x = $filter($k, $v, $originalData);
        if (!$x) continue;
        $isRaw = is_string ($v) && substr ($v, 0, 5) == '<raw>'
        ?>
        <tr>
          <th<?= $c1 ?>><?= strlen ($k) ? $k : "<i>''</i>" ?></th>
          <?php if ($typeColumn): ?>
            <td><?= $isRaw ? '' : Debug::getType ($v) ?></td>
          <?php endif ?>
          <td class="v"><?php
            if ($x === '...') echo '<i>ommited</i>';
            else if ($isRaw) echo $v;
            else echo $this->table ($v, '', $depth, $typeColumn, $columnHeaders, $maxDepth) ?></td>
          <?php endforeach; ?>
        </tbody>
      <?php } ?>
      </table><?php
    }
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
   * @param mixed $alt If not null, a replacement for inspection; type information about $val is displayed but the
   *                   inspection is performed on this argument instead.
   * @return string
   */
  private function getInspection2 ($val, $alt = null)
  {
    $arg = $this->table (isset($alt) ? $alt : $val);
    if (is_scalar ($val) || is_null ($val)) {
      $arg = '<i>(' . Debug::getType ($val) . ")</i>$arg";

      return "<#data>$arg</#data>";
    }
    if ($val instanceof \PowerString)
      return Debug::toString ($val);
    return $this->formatType ($val, $arg);
  }

}
