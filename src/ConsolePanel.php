<?php
namespace Impactwave\WebConsole;

class ConsolePanel
{
  public $title;
  public $icon;

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
  /**
   * The caption to be used on the next table.
   * @var string
   */
  protected $caption;

  function __construct ($title = 'Panel', $icon = '')
  {
    $this->title = $title;
    $this->icon  = $icon;
  }

  public function render ()
  {
    return $this->format ($this->content);
  }

  public function write ($msg)
  {
    $this->content .= $msg;
    return $this;
  }

  public function log ()
  {
    foreach (func_get_args () as $arg) {
      if (!is_string ($arg))
        $arg = $this->inspect ($arg);
      elseif (substr ($arg, 0, 2) == '<#') {
        if (substr ($arg, 2, 2) == 't>') $arg = substr ($arg, 4, -5);
        // else passthrough
      }
      elseif (substr ($arg, 0, 3) != '</#')
        $arg = $this->inspect ($arg);
      $this->write ($arg);
    }
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
    call_user_func_array ([$this, 'log'], $args);
    $this->filter = null;
    return $this;
  }

  public function withCaption ($caption)
  {
    $args         = array_slice (func_get_args (), 1);
    $this->caption = $caption;
    call_user_func_array ([$this, 'log'], $args);
    return $this;
  }

  protected function inspect ($val)
  {
    $arg = $this->table ($val);
    if (is_scalar ($val) || is_null ($val)) {
      if (!strlen ($arg))
        $arg = is_null ($val) ? 'NULL' : "''";
      $arg = '<i>(' . $this->getType ($val) . ")</i> $arg";
      return "<#data>$arg</#data>";
    }
    return "<#header>Type: <span class='__type'>".$this->getType ($val)."</span></#header>$arg";
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
          if ($args)
            $args = explode ('|', $args);
          switch ($tag) {
            case 'i':
              return "<div class='__log-item'>$str</div>";
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
            default:
              ob_clean ();
              throw new \RuntimeException("Invalid log tag <#$tag>");
          }
        }, $msg, -1, $count);
      } while ($count);
    }
    return $msg;
  }

  public function showCallLocation ()
  {
    $namespace = WebConsole::getLibraryNamespace ();
    $base      = __DIR__;
    $stack     = debug_backtrace (0);
    // Discard frames of all functions that belong to this library.
    while (!empty($stack) && (
        (isset($stack[0]['file']) && stripos ($stack[0]['file'], $base) === 0) ||
        (isset($stack[0]['class']) && stripos ($stack[0]['class'], $namespace) === 0)
      )
    ) array_shift ($stack);
    $trace = $stack[0];
    $path  = isset($trace['file']) ? $trace['file'] : '';
    $line  = isset($trace['line']) ? $trace['line'] : '';
    $shortPath = ErrorHandler::shortFileName ($path);
    $location = empty($line) ? $shortPath : ErrorHandler::errorLink($path, $line, 1, "$shortPath($line)");
    if ($path != '')
      $path = <<<HTML
<div class="__debug-location"><b>At</b> $location</div>
HTML;
    $this->write ($path);
    return $this;
  }

  protected function table ($data, $title = '')
  {
    static $depth = 0;
    if ($this->caption) {
      $title = $this->caption;
      $this->caption = '';
    }

    $nest = false;
    $w1   = WebConsole::$TABLE_PROP_WIDTH;
    $c1   = '';
    if ($data instanceof \Closure) {
      return '<i>(native code)</i>';
    }
    elseif (is_array ($data)) {
      if (empty($data))
        return '';
      if ($depth == WebConsole::$TABLE_MAX_DEPTH)
        return '<i>(...)</i>';
      ++$depth;
      $nest  = true;
      $label = 'Key';
      if (isset($data[0])) {
        $label = 'Index';
        $w1    = WebConsole::$TABLE_INDEX_WIDTH;
        $c1    = ' class="n"';
      }
      uksort ($data, 'strnatcasecmp');
    }
    elseif (is_object ($data)) {
      if ($depth == WebConsole::$TABLE_MAX_DEPTH)
        return '<i>(...)</i>';
      ++$depth;
      $data = get_object_vars($data);
      if (empty($data))
        return '';
      $nest  = true;
      $label = 'Property';
      uksort ($data, 'strnatcasecmp');
    }
    elseif (is_bool ($data))
      return $data ? 'true' : 'false';
    else {
      return htmlspecialchars (str_replace ('    ', '  ', trim (print_r ($data, true))));
    }
    $filter = isset($this->filter) ? $this->filter : function ($k) { return true; };
    ob_start (null,0);
    if ($depth >= WebConsole::$TABLE_COLLAPSE_DEPTH)
      echo '<div class="__expand"><a class="fa fa-plus-square" href="javascript:void(0)" onclick="this.parentNode.className+=\' show\'"></a>';
    ?>
    <table class="__console-table<?=$title ? ' with-caption' : '' ?>">
    <?= $title ? "<caption>$title</caption>" : '' ?>
    <colgroup>
        <col width="<?= $w1 ?>">
        <col width="<?= WebConsole::$TABLE_TYPE_WIDTH ?>">
        <col width="100%">
      </colgroup>
    <thead>
      <tr>
        <th><?= $label ?></th>
        <th>Type</th>
        <th>Value</th>
      </thead>
    <tbody>
      <?php foreach ($data as $k => $v):
      $x = $filter($k, $v, $data);
      if (!$x) continue;
      ?>
      <tr>
        <th<?= $c1 ?>><?= $k ?></th>
        <td><?= $this->getType ($v) ?></td>
        <td><?= $x === '...' ? '<i>ommited</i>' : $this->table ($v) ?></td>
        <?php endforeach; ?>
    </tbody>
    </table><?php
    if ($depth >= WebConsole::$TABLE_COLLAPSE_DEPTH)
      echo '</div>';
    if ($nest) --$depth;
    return trim (ob_get_clean ());
  }

  private function getType ($v)
  {
    if (is_object ($v)) {
      $c = get_class ($v);
      $l = array_slice (explode ('\\', $c), -1)[0];
      return "<span title='$c'>$l</span>";
    }
    if (is_array ($v))
      return 'array(' . count (array_keys ($v)) . ')';
    return gettype ($v);
  }

}
