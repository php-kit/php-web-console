<?php
namespace PhpKit\WebConsole\DebugConsole\Renderers;

class DebugConsoleRenderer
{
  static function renderConsole (array $panels)
  {
    $time   = round (microtime (true) - $_SERVER['REQUEST_TIME_FLOAT'], 3);
    $memory = round (memory_get_peak_usage (true) / 1024 / 1024, 1);
    $nop    = 'href="javascript:void(0)"';
    ?>
    <div id="__console-container">
      <div id="__debug-bar">
        <span><i class="fa fa-clock-o"></i>&nbsp; <?= $time ?> seconds</span>
        <span><i class="fa fa-cogs"></i>&nbsp; <?= $memory ?> MB</span>
        <?php foreach ($panels as $id => $panel):
          $content = $panel->render ();
          ?>
          <a id="__tab-<?= $id ?>" class="__tab hint--rounded hint--top<?= strlen ($content) ? '' : ' disabled' ?>" <?= $nop ?>
             onclick="openConsoleTab('<?= $id ?>')" data-hint="<?= $panel->title ?>">
            <?php if ($panel->icon): ?>
              <i class="<?= $panel->icon ?>"></i>
            <?php endif ?>
            <span><?= $panel->title ?></span>
          </a>
        <?php endforeach; ?>
        <a class="__minimize fa fa-chevron-down" <?= $nop ?>
           onclick="closeConsole()"></a> <a class="__close fa fa-close" <?= $nop ?>
                                            onclick="removeConsole()"></a>
      </div>
      <div id="__console">
        <?php foreach ($panels as $id => $panel): ?>
          <div id="__<?= $id ?>-tab" class="__panel"><?= $panel->render (); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- Used by goto-source links -->
    <iframe name="hidden" style="display:none"></iframe>
    <?php
  }

  static function renderScripts ()
  { ?>
    <script>
      window.find = function (s) { return document.getElementById (s) };
      window.select = function (s) { return [].concat.apply ([], document.querySelectorAll (s)) };
      window.clearTabSel =
        function (closed) {
          select ('.__tab').forEach (function (e) {
            e.className = '__tab hint--rounded hint--' + (closed ? 'top' : 'bottom') + (~e.className.indexOf('disabled') ? ' disabled' : '');
            e.setAttribute('data-hint', e.querySelector('span').textContent);
          });
        };
      window.clearSel =
        function () {
          clearTabSel ();
          select ('.__panel').forEach (function (e) {e.style.display = 'none'});
        };
      window.openConsoleTab = function (tab) {
        clearSel ();
        openConsole ();
        find ('__console').className             = 'show-console';
        find ('__' + tab + '-tab').style.display = 'block';
        var e = find ('__tab-' + tab);
        e.className = '__tab active hint--rounded hint--bottom';
        e.removeAttribute('data-hint');
      }
      window.openConsole = function (s) { find ('__console-container').className = 'Console-show' };
      window.closeConsole = function (s) {
        find ('__console-container').className = '';
        clearTabSel (true)
      };
      window.removeConsole = function (s) {
        find ('__console-container').remove ();
      };
    </script>
    <?php
  }

  static function renderStyles ()
  { ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/hint.css/1.3.6/hint.min.css">
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

      .__btn-default {
        padding: 5px 10px;
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
        top: 32px;
        -webkit-transition: top 0.1s;
        transition: top 0.1s;
      }

      #__debug-bar {
        font-family: sans-serif;
        box-sizing: border-box;
        margin: -32px 0 0 0;
        height: 32px;
        padding: 3px 0 0 10px;
        border: 1px solid #33363D;
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
        text-decoration: none;
        padding: 0 15px;
        margin-right: -3px;
        /*text-shadow: 0 1px #000;*/
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
        font-size: 12px;
      }

      #__debug-bar .__tab span {
        display: none;
      }

      #__debug-bar .__tab i {
        line-height: 26px;
      }

      #__debug-bar .__tab.active span {
        display: inline;
        margin-left: 5px;
      }

      #__debug-bar .__tab.disabled {
        cursor: default;
        color: #737373;
        pointer-events: none;
      }

      #__debug-bar .fa.big {
        font-size: 15px;
        position: relative;
        top: 1px;
      }

      #__console {
        clear: both;
        height: 100%;
        background: #EEE;
        font-family: Menlo, monospace;
        font-size: 12px;
        line-height: 1.5;
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
        overflow: scroll;
        display: none;
      }

      #__console i:not([class]) {
        color: #CCC;
        font-style: normal;
      }

      #__console sup i {
        color: #AAA;
        font-style: normal;
        margin-left: 2px;
      }

      .__close {
        line-height: 26px;
        padding: 0 10px;
        color: #FFF;
        text-decoration: none;
      }

      .__close:hover, .__close:focus {
        text-decoration: none;
        color: #FFF;
      }

      .__minimize {
        line-height: 26px;
        padding: 0 10px;
        color: #777;
        text-decoration: none;
        cursor: default;
      }

      .__minimize:hover, .__minimize:focus {
        text-decoration: none;
        color: #777;
      }

      .Console-show .__minimize {
        cursor: pointer;
        color: #FFF;
        text-shadow: 0 1px #000;
      }

      .__log-section {
        counter-reset: log-section;
        margin-top: -1px;
        border: 1px solid #DDD;
        background: #FFF;
        position: relative;
      }

      .__log-title {
        font-weight: bold;
        text-align: left;
        font-family: sans-serif;
        padding: 10px;
        background: #eeeef0;
        border-bottom: 1px solid #DDD;
        color: #888;
      }

      .__log-stripe {
        position: relative;
      }

      .__log-data {
        white-space: pre;
        padding: 10px 5px 0;
        display: inline-block;
      }

      /*
      .__log-data:first-child {
        padding-top: 10px;
      }

      .__log-data:last-child {
        padding-bottom: 10px;
      }
      */
      /* must lie inside a __log-stripe */
      .__debug-location {
        color: #999;
        right: 10px;
        top: 10px;
        z-index: 1;
        position: absolute;
      }

      .__debug-location a {
        text-decoration: none;
      }

      .__debug-location a:link {
        color: #99C;
      }

      .__debug-location a {
        color: #AAA;
      }

      .__console-table + .__debug-location {
        margin-top: -7px;
      }

      .__console-table td.v {
        white-space: pre-wrap;
      }

      #__console .__log-item {
        margin: 10px 10px 0;
      }

      #__console .__log-item:last-child {
        margin-bottom: 10px;
      }

      #__console .__header {
        color: #666;
        font-weight: bold;
        padding: 10px;
      }

      #__console .__header small {
        color: #999;
        font-weight: normal;
      }

      #__console .__footer {
        color: #777;
        float: right;
        font-size: 10px;
        padding: 10px;
        display: inline-block;
      }

      #__console .__footer + div {
        clear: right;
      }

      #__console .__alert {
        color: #C00;
        background: #FFA;
        line-height: 10px;
        padding: 12px;
        border-bottom: 1px solid #DDD;
      }

      #__console .__comment {
        olor: #999;
        margin: -1px 0;
        background: #fff;
        padding: 5px 10px;
        border: 1px solid #eee;
        border-left: 5px solid #ccc;
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

      #__console a {
        color: #888;
      }

      #__console a.fa {
        text-decoration: none;
      }

      #__console span[title], #__console .__type {
        color: #5A5;
        font-weight: normal;
        cursor: help;
      }

      #__console kbd {
        font-family: menlo, monospace;
        font-weight: normal;
        background: #fff1cc;
        border: 1px solid #ffde81;
        box-shadow: none;
        padding: 2px 5px;
        color: #333;
        font-size: 12px;
      }

      .__console-table {
        font-size: 12px;
        width: 100%;
        min-width: 320px; /* Prevent overflow of value column label */
        table-layout: fixed;
        border-spacing: 0;
        border-collapse: collapse;
        border: none;
        margin: -1px 0;
      }

      .__debug-location + .__console-table {
        margin-top: -10px;
      }

      .__console-table + .__console-table {
        margin-top: 10px;
      }

      .__console-table .__console-table {
        border: none !important;
        margin: 0 0 -1px -11px;
        width: 100%;
        width: calc(100% + 11px);
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

      .__console-table td[align=right] {
        padding: 0 10px 0 0;
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
        color: #888 !important;
        text-decoration: none;
        line-height: 24px;
      }

      .__expand.show > table {
        display: table;
      }

      .__expand.show > a {
        display: none;
      }

      .__panel > code:first-child {
        box-sizing: border-box;
        height: 100%;
        overflow: auto;
      }

      .__panel code {
        background: #FFF;
        color: #777;
        display: block;
        padding: 10px;
        font-family: Menlo, monospace;
        font-size: 10px;
        line-height: 1.2;
        white-space: pre;
        border-radius: 0;
      }

      .__panel code b {
        color: #F66;
        font-weight: normal;
      }

      .__panel code table {
        background: #FFF;
        font-size: inherit;
        border-spacing: 0;
        line-height: 1.2;
        margin: 15px 0;
      }

      .__panel code table td {
        vertical-align: top;
        border: 1px solid #eee;
        padding: 3px 5px 2px;
      }

      .__panel code table th {
        vertical-align: top;
        font-weight: normal;
        color: #08A;
        text-align: left;
        padding-right: 10px;
      }

      .__rowHeader {
        counter-increment: log-section;
        margin: 0 0 -1px 0 !important;
        border: 1px solid #EEE;
        background: #FFF;
      }

      .__rowHeader::before {
        content: counter(log-section);
        display: inline-block;
        border-right: 1px solid #DDD;
        border-bottom: 1px solid #DDD;
        text-align: right;
        box-sizing: border-box;
        min-width: 33px; /* 2 digits'width */
        padding: 4px 9px;
        margin-right: 10px;
        margin-bottom: -1px;
        color: #B00;
        background: #F8F8F8;
      }

      .__panel .indent {
        background: #F8F8F8;
        padding-left: 33px;
      }

      .__panel .indent caption {
        padding: 5px 10px;
        background: #f0f0f0;
        border-color: none;
        border-top: 1px solid #EEE;
        font-weight: normal;
        outline: 1px solid #DDD;
        outline-offset: -1px;
        margin-bottom: -1px;
      }

      .__panel .indent > table {
        outline: 1px solid #DDD;
        outline-offset: -1px;
      }

      .__log-section > .indent {
        padding-left: 33px;
      }

    </style>
    <?php
  }

}
