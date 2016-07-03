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
        document.documentElement.style.paddingBottom = null;
      };
      document.documentElement.style.paddingBottom = "32px";
    </script>
    <?php
  }

  static function renderStyles ()
  { ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/hint.css/1.3.6/hint.min.css">
    <style>
      .hint:after, [data-hint]:after {
        padding: 8px 15px;
        white-space: pre;
        line-height: 20px;
      }

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
        -webkit-transition: top 0.5s;
        transition: top 0.5s;
      }

      #__console-container.Console-show {
        top: 32px;
        -webkit-transition: top 0.5s;
        transition: top 0.5s;
      }

      #__debug-bar {
        font-family: sans-serif;
        box-sizing: border-box;
        margin: -32px 0 0 0;
        height: 32px;
        padding: 3px 0 0 10px;
        border-top: 1px solid #CCC;
        background: #DDD;
        /*box-shadow: 0 -1px 5px rgba(0,0,0,0.1);*/
        text-align: right;
        color: #666;
      }

      .Console-show #__debug-bar {
        background: #DDD;
        border-top: none;
        border-bottom: 1px solid #CCC;
      }

      #__debug-bar > span {
        display: inline-block;
        float: left;
        line-height: 28px;
        padding: 4px 10px 0;
        margin-top: -4px;
        border-left: 1px solid #EEE;
        border-right: 1px solid #CCC;
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
        border-left: 1px solid #DDD;
      }

      #__debug-bar .__tab {
        display: inline-block;
        color: #666;
        text-decoration: none;
        padding: 0 15px;
        margin-right: -3px;
        /*text-shadow: 0 1px #000;*/
        line-height: 26px;
      }

      #__debug-bar .__tab.active {
        background: #EEE;;
        border: 1px solid #CCC;
        border-bottom: 1px solid #EEE;
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
        color: #CCC;
        pointer-events: none;
      }

      #__debug-bar .fa.big {
        font-size: 15px;
        position: relative;
        top: 1px;
        line-height: 23px;
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

      #__console sup {
        line-height: 0;;
      }
      #__console sup i {
        color: #AAA;
        font-style: normal;
        margin-left: 2px;
        vertical-align: baseline;
        position: relative;
        top: -2px;
      }

      .__close,
      .__minimize {
        line-height: 26px;
        text-decoration: none;
      }
      .__close {
        padding: 0 15px;
      }
      .__minimize,
      .__minimize:hover,
      .__minimize:focus {
        padding: 0 5px 0 10px;
        color: #CCC;
      }

      .__minimize:hover, .__minimize:focus {
        text-decoration: none;
      }

      .Console-show .__minimize,
      .Console-show .__minimize:hover {
        cursor: pointer;
        color: #666;
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
        padding: 30px 0 0;
        border: 1px solid #DDD;
        margin-top: -1px;
      }
      .__log-stripe .__log-item {
        margin-top: -45px;;
      }

      .__log-data {
        white-space: pre;
        /*padding: 0 5px 0;*/
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
        right: 15px;
        top: 6px;
        z-index: 1;
        position: absolute;
      }

      /*.__console-table + .__debug-location {*/
        /*margin-top: -7px;*/
      /*}*/

      .__console-table td.v {
        white-space: pre-wrap;
      }

      #__console .__log-item {
        /*background: #fff;*/
        padding: 10px;
        border-top: 1px solid #e4e4e4;
      }

      #__console .__log-item:last-child {
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
        padding: 12px;
        border-bottom: 1px solid #DDD;
      }

      #__console .__alert + div {
        margin-top: 20px;
      }

      #__console .__comment {
        color: #999;
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
        color: #99C;
      }

      #__console a.fa {
        text-decoration: none;
      }

      #__console span[title], #__console .__type {
        color: #595;
        font-weight: normal;
        font-family: Menlo, monospace;
      }
      #__console span[title] {
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
        margin-top: 5px;
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
        border-top: none;
        color: #B00;
      }

      .__console-table > tbody > tr > td {
        vertical-align: top;
        word-break: break-all;
        white-space: pre-line;
        border: 1px solid #EEE;
        border-top: none;
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

      .__component .__console-table {
        width: auto;
        table-layout: auto;
      }
      .__component .__console-table td,
      .__component .__console-table th
      {
        line-height: 15px;
      }
      .__component .__console-table tr:first-of-type td,
      .__component .__console-table tr:first-of-type th
      {
        border-top: 1px solid #EEE;
      }
      .__component > .__console-table {
        margin: 0 0 0 15px;
      }
      .__component .__console-table .__console-table  {
        margin: -1px -1px -1px -11px;
      }
      .__component .__modified th,
      .__component .__modified td
      {
        background-color: #FFE !important;
      }
      .__component .__original {
        opacity: 0.5;
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

      .__panel .icon {
        margin-left: 5px;
        color: #AAA;
        cursor: help;
      }
      .__panel .icon:hover {
        color: #333;
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
        font-size: 12px;
        line-height: 1.3;
        white-space: pre;
        border-radius: 0;
      }

      .__panel code b {
        color: #F66;
        font-weight: normal;
      }

      .__rowHeader {
        counter-increment: log-section;
        margin: 0 0 -1px 0 !important;
        border: 1px solid #EEE;
        background: #FFF;
        padding: 0 !important;
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
