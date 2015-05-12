<?php
namespace Impactwave\WebConsole\Renderers;

class WebConsoleRenderer
{

  static function renderConsole (array $panels)
  {
    $time = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
    $memory = round (memory_get_peak_usage (true) / 1024 / 1024, 1);
    $nop = 'href="javascript:void(0)"';
    ?>
    <div id="__console-container">
      <div id="__debug-bar">
        <span><i class="fa fa-clock-o"></i>&nbsp; <?=$time ?> seconds</span>
        <span><i class="fa fa-cogs"></i>&nbsp; <?=$memory ?> MB</span>
        <?php foreach ($panels as $id => $panel):
          $content = $panel->render ();
          ?>
          <a class="__tab<?= strlen ($content) ? '' : ' disabled' ?>" <?= $nop ?>
             onclick="clearSel();openConsole();find('__console').className='show-console';find('__<?= $id ?>-tab').style.display='block';this.className='__tab active'">
          <?php if ($panel->icon): ?>
            <i class="<?= $panel->icon ?>"></i>
          <?php endif ?>
            <?= $panel->title ?>
        </a>
        <?php endforeach; ?>
        <a class="__close fa fa-close" <?= $nop ?>
           onclick="find('__console-container').className='';clearSel()"></a>
      </div>
      <div id="__console">
        <?php foreach ($panels as $id => $panel): ?>
          <div id="__<?= $id ?>-tab" class="__panel"><?= $panel->render (); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
  }

  static function renderScripts ()
  { ?>
    <script>
    window.find = function (s) { return document.getElementById (s) };
    window.select = function (s) { return [].concat.apply ([], document.querySelectorAll (s)) };
    window.clearSel =
      function ()
      {
        select ('.__tab').forEach (function (e) {e.className = e.className.replace (' active', '')});
        select ('.__panel').forEach (function (e) {e.style.display = 'none'});
      };
    window.openConsole = function (s) { find ('__console-container').className = 'Console-show' };
  </script>
    <?php
  }

  static function renderStyles ()
  { ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css">
    <style>
.__btn {
  text-decoration: none;
  background: #EEE;
  border: 1px solid #CCC;
  color: #666;
  font-family: sans-serif;
  width: 70px;
  text-align: center;
  box-shadow: 1px 1px 0 rgba(0, 0, 0, 0.03), inset 0 15px 10px rgba(255, 255, 255, 0.9);
}

.__btn:focus {
  outline: 1px dotted #ddddf6;
  outline-offset: 3px;
}

.__btn:active {
  background: #f8f8f8;
  outline: none;
  box-shadow: none;
}

#__console-container {
  position: fixed;
  z-index: 9999;
  top: 100%;
  bottom: 0;
  left: 0;
  right: 0;
  font-size: 12px;
  -webkit-transition: top 0.2s;
  transition: top 0.2s;
}

#__console-container.Console-show {
  top: 33px;
  -webkit-transition: top 0.1s;
  transition: top 0.1s;
}

#__debug-bar {
  box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
  box-sizing: border-box;
  margin: -32px 0 0 0;
  height: 32px;
  padding: 3px 0 0 10px;
  border: 1px solid #73787E;
  border-right-color: #33363d;
  border-bottom-color: #33363d;
  outline: 1px solid #272a2f;
  background-color: #EEE;
  background-image: linear-gradient(0deg, #42454a, #545b61);
  text-align: right;
}

#__debug-bar > span {
  display: inline-block;
  float: left;
  color: #FFF;
  line-height: 28px;
  padding: 4px 10px 0;
  margin-top: -4px;
  border-left: 1px solid #686d73;
  border-right: 1px solid #000;
  position: relative;
}
#__debug-bar > span:first-of-type {
  border-left: none;
  padding-left: 0;
}

#__debug-bar > span:last-of-type::after {
  content: '';
  display: inline-block;
  position: absolute;
  height: 32px;
  right: -2px;
  top: 0;
  border-left: 1px solid #686d73;
}

#__debug-bar .__tab {
  display: inline-block;
  color: #FFF;
  font-family: sans-serif;
  text-decoration: none;
  padding: 0 15px;
  margin-right: -3px;
  text-shadow: 0 1px #000;
  background-color: #666;
  background-image: linear-gradient(0deg, #42454a, #545b61);
  border-radius: 4px 4px 0 0;
  box-shadow: inset 1px 1px 0px rgba(255, 255, 255, 0.1);
  line-height: 26px;
  border: 1px solid #33363d;
}

#__debug-bar .__tab.active {
  background: #EEE;
  color: #333;
  text-shadow: none;
  padding-bottom: 1px;
}

#__debug-bar .__tab i {
  margin-right: 5px;
}

#__debug-bar .__tab.disabled {
  cursor: default;
  color: #737373;
  pointer-events: none;
}

#__console {
  clear: both;
  height: 100%;
  background: #EEE;
  font-family: Menlo, monospace;
  font-size: 12px;
  line-height: 1.2;
  box-sizing: border-box;
  position: relative;
}

#__console > div {
  position: absolute;
  top: 5px;
  left: 5px;
  bottom: 5px;
  right: 5px;
  border: 1px solid #CCC;
  white-space: pre-line;
  padding: 10px;
  background: #FFF;
  overflow: scroll;
  display: none;
}

#__console i:not([class]) {
  color: #CCC;
  font-style: normal;
}

.__close {
  line-height: 26px;
  padding: 0 10px;
  color: #777;
  text-decoration: none;
  cursor: default;
}

.Console-show .__close {
  cursor: pointer;
  color: #FFF;
  text-shadow: 0 1px #000;
}

.__log-section {
  margin: 0 0 20px;
  padding: 0 10px 10px;
  border: 1px solid #DDD;
  position: relative;
}

.__log-section:last-child {
  margin-bottom: 0;
}

.__log-title {
  font-weight: bold;
  text-align: left;
  font-family: sans-serif;
  margin: 0 -10px 10px;
  padding: 10px;
  background: #eeeef0;
  border-bottom: 1px solid #DDD;
  color: #888;
}

.__log-stripe {
  padding: 10px 0;
  position: relative;
}

.__log-stripe:first-child {
  padding-top: 0;
}

.__log-data {
  white-space: pre;
  padding-top: 5px;
}

.__log-data > .__console-table:first-of-type {
  margin-top: -16px;
}

.__log-data > .__console-table:not(.with-caption):first-of-type {
  margin-top: -15px;
}

.__log-data > .__console-table:last-of-type {
  margin-bottom: -10px;
}

/* must lie inside a __log-stripe */
.__debug-location {
  color: #999;
  right: 10px;
  top: 5px;
  z-index: 1;
  position: absolute;
}

#__console .__header {
  color: #666;
  font-weight: bold;
  margin: 10px 0;
}

#__console .__footer {
  color: #777;
  text-align: right;
  font-size: 10px;
}

#__console .keyword {
  color: #B00;
  margin: 0 0 20px;
}

#__console .identifier {
  color: #55A;
}

#__console .dbcolumn {
  color: #5A5;
}

.__console-table {
  font-size: 12px;
  width: 100%;
  width: calc(100% + 20px);
  min-width: 320px; /* Prevent overflow of value column label */
  table-layout: fixed;
  border-spacing: 0;
  margin: -11px -10px;
  border-collapse: collapse;
}

.__debug-location + .__console-table {
  margin-top: -10px;
}

.__debug-item + .__console-table {
  margin-top: 10px;
}

.__console-table .__console-table {
  border: none !important;
  margin: 0 0 -1px -11px;
  width: 100%;
  width: calc(100% + 11px);
}

.__console-table + .__console-table {
  margin-top: 10px;
}

.__console-table caption {
  font-weight: bold;
  text-align: left;
  font-family: sans-serif;
  margin: 0;
  padding: 10px;
  background: #eeeef0;
  border-top: 1px solid #DDD;
  border-bottom: 1px solid #DDD;
  color: #888;
}

.__console-table th, .__console-table td {
  padding: 0 0 0 10px;
  line-height: 24px;
}

.__console-table > thead > tr > th {
  border-bottom: 1px solid #DDD;
  background: #fdfdfe;
  font-family: sans-serif;
  text-align: left;
  font-weight: normal;
  color: #5AA;
}

.__console-table > tbody > tr > th {
  font-weight: normal;
  text-align: left;
  vertical-align: top;
  word-break: break-all;
  border: 1px solid #EEE;
  color: #B00;
}

.__console-table > tbody > tr > td {
  vertical-align: top;
  word-break: break-all;
  white-space: pre-line;
  border: 1px solid #EEE;
}

.__console-table .n {
  text-align: right;
  padding-right: 10px;
  padding-left: 0;
}

.__console-table td:nth-child(2) {
  color: #55A;
}

.__console-table span[title] {
  color: #5A5;
  cursor: help;
}

.__console-table tr:nth-child(even) > td,
.__console-table tbody > tr:nth-child(even) > th {
  background-color: #FDFDFE;
}

.__console-table tr:nth-child(odd) > td,
.__console-table tbody > tr:nth-child(odd) > th {
  background-color: #FFF;
}

.__expand > table {
  display: none;
}

.__expand > a {
  color: #888;
  text-decoration: none;
  line-height: 24px;
}

.__expand.show > table {
  display: table;
}

.__expand.show > a {
  display: none;
}
    </style>
    <?php
  }

}