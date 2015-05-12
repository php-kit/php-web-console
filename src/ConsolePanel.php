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

  function __construct ($title = 'Panel', $icon = '')
  {
    $this->title = $title;
    $this->icon  = $icon;
  }

  public function render ()
  {
    return $this->content;
  }

  public function write ($msg)
  {
    $this->content .= $msg;
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   * Params: list of one or more values to be displayed.
   * @param string $title The logging section title.
   * @return void
   */
  public function debugSection ($title)
  {
    $this->write ("<div class='__log-section'><div class='__log-title'>$title</div>");
    $args = func_get_args ();
    array_shift ($args);
    $me = get_class ();
    call_user_func_array ("$me::debug", $args);
    $this->write ('</div>');
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   *
   * > The filter function may remove some keys from the tabular output of objects or arrays.
   *
   * Extra params: list of one or more values to be displayed.
   * @param callable $fn Filter callback. Receives the key name, the value and the target object/array.
   *                     Returns <code>true</code> if the value should be displayed.
   * @return void
   */
  public function debugWithFilter (callable $fn)
  {
    $args         = array_slice (func_get_args (), 1);
    $this->filter = $fn;
    call_user_func_array ([get_class (), 'debug'], $args);
    $this->filter = null;
  }

  public function logSection ($title)
  {
    $this->write ("<div class='__log-section'><div class='__log-title'>$title</div>");
    $args = func_get_args ();
    array_shift ($args);
    $me = get_class ();
    call_user_func_array ("$me::log", $args);
    $this->write ('</div>');
  }

  public function log ()
  {
    $this->write ('<div class="__log-stripe">');
    foreach (func_get_args () as $text) {
      if (!is_string ($text))
        $text = $this->table ($text);
      $this->write ($text);
    }
    $this->write ('</div>');
  }

  /**
   * Logs detailed information about the specified values or variables to the PHP console.
   * Params: list of one or more values to be displayed.
   * @return void
   */
  public function debug ()
  {
    $this->write ('<div class="__debug-stripe">');
    $this->showCallLocation ();
    foreach (func_get_args () as $val) {
      $text = $this->table ($val);
      if (is_scalar ($val) || is_null ($val)) {
        if (!strlen ($text))
          $text = is_null ($val) ? 'NULL' : "''";
        $text .= ' <i>(' . $this->getType ($val) . ')</i>';
      }
      $this->write (strlen ($text) && $text[0] == '<' ? $text : "<div class='__debug-item'>$text</div>");
    }
    $this->write ('</div>');
  }

  protected function showCallLocation ()
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
    $path  = ErrorHandler::shortFileName ($path);
    $line  = isset($trace['line']) ? " (<b>{$trace['line']}</b>)" : '';
    if ($path != '')
      $path = <<<HTML
<div class="__debug-location"><b>At</b> $path$line</div>
HTML;
    $this->write ($path);
  }

  protected function table ($data, $title = '')
  {
    static $depth = 0;

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
      $data = (array)$data;
      if (empty($data))
        return '';
      if ($depth == WebConsole::$TABLE_MAX_DEPTH)
        return '<i>(...)</i>';
      ++$depth;
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
    ob_start ();
    if ($depth >= WebConsole::$TABLE_COLLAPSE_DEPTH)
      echo '<div class="__expand"><a class="fa fa-plus-square" href="javascript:void(0)" onclick="this.parentNode.className+=\' show\'"></a>';
    ?>
    <table class="__console-table">
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
      <?php foreach ($data as $k => $v): ?>
      <tr>
        <th<?= $c1 ?>><?= $k ?></th>
        <td><?= $this->getType ($v) ?></td>
        <td><?= $filter($k, $v, $data) ? $this->table ($v) : '<i>ommited</i>' ?></td>
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