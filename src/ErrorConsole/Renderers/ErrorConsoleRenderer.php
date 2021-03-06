<?php
namespace PhpKit\WebConsole\ErrorConsole\Renderers;

use Error;
use Exception;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;

class ErrorConsoleRenderer
{
  static function friendlyClass ($class)
  {
    $s    = explode ('\\', $class);
    $name = end ($s);
    $name = preg_replace ('/(?=[AEIOU])/', ' ', $name);
    return "<span class='info' title='$class'>" . trim ($name) . "</span>";
  }

  static function getIcon ()
  {
    return "data:image/png;base64,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";
  }

  /**
   * Note: if the exception has a `getTitle()` method, that value is displayed as the popup's header, otherwise the
   * exception's class name will be shown instead.
   *
   * @param Exception|Error $exception
   * @param string          $popupTitle
   * @param string          $stackTrace
   */
  static function renderPopup ($exception, $popupTitle, $stackTrace)
  {
    self::renderStyles ();
    ?>
    <!DOCTYPE HTML><html>

    <head>
      <meta charset="UTF-8">
      <title><?= $popupTitle ?></title>
    </head>

    <body id="__error">
      <div id="__panel">
        <div class="__title-bar"><?= $popupTitle ?></div>
        <div class="__panel-body">
          <div id="__feedback">Please switch to PHPStorm/IDEA to view the code at the error location.</div>
          <img src="<?= self::getIcon () ?>">
          <div class="__message">
            <?php
            $title = method_exists ($exception, 'getTitle') ? $exception->getTitle ()
              : self::friendlyClass (get_class ($exception));
            if ($title)
              echo "<h3>$title</h3>";
            echo "<div>" . ucfirst (ErrorConsole::processMessage ($exception->getMessage ())) . "</div>";
            if (!empty ($exception->info)) echo "<div class='__info'>$exception->info</div>";
            ?>
          </div>
          <div id="__error-location">
            <?php
            $link = ErrorConsole::errorLink ($exception->getFile (), $exception->getLine (), 1);
            if ($link)
              echo "Thrown from $link, line <b>{$exception->getLine()}</b>";
            ?>
            <div class="__more">
              <a id="__more"
                 class=" __btn"
                 href="javascript:void(document.getElementById('__panel').className='__show')"
                 onclick="this.style.display='none';window.setTimeout(function(){document.body.scrollTop=document.getElementById('__error-location').offsetTop})"> Stack trace
                <span style="font-size:16px">&blacktriangledown;</span></a>
            </div>
          </div>
        </div>
        <div id="__trace">
          <?= $stackTrace ?>
        </div>
        <iframe name="hidden" style="display:none"></iframe>
    </body><html>
    <?php
  }

  /**
   * @param int    $rowNum  Row number.
   * @param string $fname   File name.
   * @param string $lineStr Line number.
   * @param string $fn      Method name.
   * @param string $args    Call arguments.
   * @param string $at      Full error location.
   * @param string $edit    Edit button.
   */
  static function renderStackFrame ($rowNum, $fname, $lineStr, $fn, $args, $at, $edit = '')
  { ?>
    <div class="stack-frame">
      <div class="code">
        <div>
          <span class="rowHeader"><?= $rowNum ?></span>
          <?= $fname ?>
          <?= $lineStr ?>
          <div class="__call">
            <?= "$fn $args" ?>
            <?= $edit ?>
            <a class="__btn" href="javascript:void(0)" onclick="this.nextSibling.nextSibling.style.display='block'">
              more...
            </a>
            <div class='__location'>At <?= $at ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  static function renderStyles ()
  { ?>
    <style>
      #__error {
        margin: 0;
        background: #f8f8f8;
        overflow-x: hidden;
      }

      #__error a {
        text-decoration: none;
      }

      #__error a:link {
        color: #6a86b4
      }

      #__error a:visited {
        color: #AAA
      }

      #__error a:focus {
        outline: none;
        text-decoration: underline;
      }

      #__error h5 {
        color: #488;
        margin: 15px 0;
        font-size: 14px;
      }

      #__error h6 {
        margin: 15px 0;
        font-size: 12px;
        text-transform: uppercase;
      }

      #__error hr {
        margin: 20px 0;
        border: 0;
        border-top: 1px solid #BF3D27;
      }

      #__error blockquote {
        background: #eaebec;
        border: 1px solid #d7d7df;
        border-left: 4px solid #BF3D27;
        padding: 15px;
        margin-left: 0;
        color: #999;
      }

      #__panel {
        text-align: left;
        font-family: "Helvetica Neue", Arial, Verdana, sans-serif;
        font-size: 14px;
      }

      #__error .__panel-body {
        /*padding-top: 20px;*/
      }

      #__error .__title-bar {
        /*background: #b24734;*/
        /*border: 1px solid #94301e;*/
        /*color: #f7e6e4;*/
        /*text-align: left;*/
        /*padding: 5px 30px;*/
        font-size: 18px;
        padding: 5px 15px;
        float: right;
        z-index: 2;
        position: relative;
      }

      #__feedback {
        display: none;
        color: #666;
        background: #FFE;
        padding: 20px 15px 10px;
        border-bottom: 1px solid #e9e9d8;
      }
      #__feedback code {
        border: 1px solid #CCC;
        padding: 0 5px;
        background: #FFF;
        margin: 0 5px;
      }
      #__feedback h4 {
        margin: 0 0 20px;
      }

      #__error img {
        position: absolute;
        margin: 8px 20px;
        z-index: 2;
      }

      #__error .__message {
        padding: 26px 20px 25px 88px;
        border-bottom: 1px solid #ddd;
        color: #555;
        z-index: 1;
        position: relative;
        background: #FFF;
      }

      #__error .__more {
        text-align: right;
      }

      #__more {
        display: inline-block;
        position: relative;
        line-height: 24px;
        margin-top: 20px;
        padding: 0 15px 0 5px;
        font-size: 10px;
        text-decoration: none;
        font-family: sans-serif;
      }

      #__more span {
        position: absolute;
        right: 8px;
      }

      #__error .stack-frame {
        line-height: 18px;
        box-sizing: border-box;
        border: 1px solid #DDD;
        margin-top: -1px;
      }

      #__error .code {
        display: table;
        table-layout: fixed;
        border: 1px #DDD;
        border-style: solid none;
        border-spacing: 0;
        width: 100%;
        margin-top: -1px;
      }

      #__error .code:last-child {
        border-bottom: none;
      }

      #__error .code > div {
        display: table-row;
      }

      #__error td > code {
        border: none;
        padding: 0;
      }

      #__error .rowHeader {
        display: table-cell;
        padding: 5px 15px;
        width: 22px;
        text-align: right;
        border-right: 1px solid #DDD;
      }

      #__error .file {
        display: table-cell;
        background: #EEE;
        padding: 5px 0 0 15px;
        width: 200px;
        text-align: left;
        border-right: 1px solid #DDD;
        overflow-x: hidden;
      }

      #__error .line {
        border-right: 1px solid #DDD;
        display: table-cell;
        background: #EEE;
        padding: 5px 15px 0 0;
        width: 50px;
        text-align: right;
      }

      #__error .__call {
        display: table-cell;
        color: #333;
        padding: 5px 10px;
        background: #FFF;
        white-space: normal;
      }

      #__error .__call > a:not(.tag) {
        float: right;
        margin-left: 10px;
      }

      #__error a.__btn {
        text-decoration: none;
        background: #EEE;
        border: 1px solid #CCC;
        color: #666;
        font-family: sans-serif;
        width: 92px;
        text-align: center;
        box-shadow: 1px 1px 0 rgba(0, 0, 0, 0.03), inset 0 15px 10px rgba(255, 255, 255, 0.9);
      }

      #__error a.__btn:focus {
        outline: 1px dotted #ddddf6;
        outline-offset: 3px;
      }

      #__error a.__btn:active {
        background: #f8f8f8;
        outline: none;
        box-shadow: none;
      }

      #__trace {
        font-family: menlo, monospace;
        font-size: 12px;
        display: none;
        color: #555;
        white-space: nowrap;
      }

      .__show #__trace {
        display: block;
      }

      #__error .__message kbd {
        font-family: menlo, monospace;
        font-weight: normal;
        background: #fff1cc;
        border: 1px solid #ffde81;
        box-shadow: none;
        padding: 2px 5px;
        color: #333;
        font-size: 12px;
      }

      #__error .class {
        cursor: help;
        color: #A00;
      }

      #__error .fn {
        color: #08A;
      }

      #__error .string {
        color: #C90;
      }

      #__error .type {
        color: #595;
      }

      #__error .__type {
        cursor: help;
        color: #595;
        font-family: Menlo, monospace;
      }

      #__error h3 .info {
        color: #BF3D27;
      }

      #__error .tag {
        color: #5AA;
      }

      #__error .tag-hilight {
        color: #F00;
      }

      #__error-location {
        padding: 20px;
        /*border-top: 1px solid #BF3D27;*/
        /*background: #FFF;*/
        font-family: menlo, monospace;
        font-size: 12px;
      }

      #__error .__location {
        display: none;
        color: #999;
        font-size: 10px;
        padding: 5px 10px;
        margin: 5px -10px -5px -10px;
      }

      #__error .__info {
        background: #dbfbff;
        border: 1px solid #bdd5d9;
        font-size: 12px;
        color: #777;
        padding: 15px;
        margin: 30px 15px 15px 0;
      }

      .__message h3 {
        font-size: 16px;
        margin: -4px 0 30px;
      }

      .__message h3 b {
        color: #A00;
      }

      .__message .fixed {
        font-family: menlo, monospace;
      }

      .__message code {
        overflow: auto;
        max-height: 160px;
        background: #FFF;
        color: #777;
        display: block;
        border: 1px solid #DDD;
        padding: 10px;
        font-family: Menlo, monospace;
        font-size: 13px;
        line-height: 1.2;
        white-space: pre;
      }

      .__message code b {
        color: #F66;
        font-weight: normal;
      }

      .__message table.grid {
        /*margin: 30px 0;*/
      }

      .__message table.grid i {
        color: #CCC;
      }

      .__message table.grid th {
        border: 1px solid #CCC;
        padding: 5px 10px;
        background: #EEE;
        max-width: 160px;
      }

      .__message table.grid td {
        border: 1px solid #CCC;
        padding: 5px 10px;
        background: #FFF;
      }

      .__message table {
        border-collapse: collapse;
        font-size: inherit;
        border-spacing: 0;
        line-height: 1.2;
        margin: 15px 0;
      }

      .__message table td {
        padding: 0 15px 0 0;
        vertical-align: top;
      }

      .__message table th {
        vertical-align: top;
        font-weight: normal;
        color: #08A;
        text-align: left;
        padding-right: 10px;
      }

    </style>
    <?php
  }

}
