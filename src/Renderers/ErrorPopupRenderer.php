<?php
namespace Impactwave\WebConsole\Renderers;

use Exception;
use Impactwave\WebConsole\ErrorHandler;

class ErrorPopupRenderer
{
  /**
   * @param Exception $exception
   * @param string    $popupTitle
   * @param string    $stackTrace
   */
  static function renderPopup (Exception $exception, $popupTitle, $stackTrace)
  {
    ob_clean ();
    self::renderStyles ();
    ?>
    <table id="__error">
    <tr>
    <td valign="center" align="center">
      <div id="__panel">
        <div class="__title-bar"><?= $popupTitle ?>
          <span onclick="document.getElementById('__error').style.display='none'">&#xD7;</span>
        </div>
        <div class="__panel-body">
          <div id="__feedback">Please switch to PHPStorm / IDEA to view the code at the error location.</div>
          <img src="<?= self::getIcon () ?>">
          <div class="__message">
            <?php
            $title = isset($exception->title) ? $exception->title : get_class ($exception);
            if ($title)
              echo "<h3>$title</h3>";
            echo ucfirst (ErrorHandler::processMessage ($exception->getMessage ()));
            ?>
          </div>
          <div class="error-location">
            <?php
            $link = ErrorHandler::errorLink ($exception->getFile (), $exception->getLine (), 1);
            if ($link)
              echo "Thrown from $link, line <b>{$exception->getLine()}</b>";
            ?>
            <div class="__more">
             <a id="__more"
                class=" __btn"
                href="javascript:void(document.getElementById('__panel').className='__show')"
                onclick="this.style.display='none'">
               Stack trace
               <span style="font-size:16px">&blacktriangledown;</span>
            </a>
          </div>
        </div>
      </div>
      <div id="__trace">
        <?= $stackTrace ?>
      </div>
    </td>
    </tr>
    </table>
    <script>document.getElementById ('__more').focus ()</script>
    <iframe name="hidden" style="display:none"></iframe>
    <?php
  }

  /**
   * @param string $fname   File name.
   * @param string $lineStr Line number.
   * @param string $fn      Method name.
   * @param string $args    Call arguments.
   * @param string $at      Full error location.
   * @param string $edit    Edit button.
   */
  static function renderStackFrame ($fname, $lineStr, $fn, $args, $at, $edit = '')
  { ?>
    <div class="stack-frame">
      <div class="code">
        <div>
          <?= $fname ?>
          <?= $lineStr ?>
          <div class="__call">
            <?= "$fn $args" ?>
            <?= $edit ?>
            <a class="__btn" href="javascript:void(0)" onclick="this.nextSibling.nextSibling.style.display='block'">more...</a>
            <div class='__location'>At <?= $at ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  static function getIcon ()
  {
    return "data:image/png;base64,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";
  }

  static function renderStyles ()
  { ?>
    <style>
#__error {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(208, 208, 208, 0.9);
}

#__error a {
  text-decoration: none;
}

#__error a:link {
  color: #99C
}

#__error a:visited {
  color: #AAA
}

#__error a:focus {
  outline: none;
  text-decoration: underline;
}

#__panel {
  display: inline-block;
  position: relative;
  min-width: 512px;
  max-width: 1024px;
  min-height: 128px;
  text-align: left;
  border: 1px solid #73787E;
  background: #F5F5F5;
  font-family: "Helvetica Neue", Arial, Verdana, sans-serif;
  font-size: 14px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
}

#__error .__panel-body {
  border-top: 1px solid #FFF;
}

#__error .__title-bar {
  background-color: #EEE;
  background-image: linear-gradient(0deg, #42454a, #545b61);
  outline: 1px solid #272a2f;
  border: 1px solid #73787E;
  border-right-color: #33363d;
  border-bottom: none;
  color: #FFF;
  text-shadow: #000 1px 1px;
  text-align: center;
  font-size: 14px;
  position: relative;
  line-height: 25px;
  padding-top: 3px;
}

#__error .__title-bar span {
  position: absolute;
  right: 13px;
  cursor: pointer;
  top: 2px;
}

#__feedback {
  display: none;
  color: #666;
  background: #FFE;
  padding: 10px 15px;
  border-bottom: 1px solid #e9e9d8;
}

#__error img {
  position: absolute;
  margin: 8px 20px;
}

#__error .__message {
  padding: 26px 20px 20px 88px;
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
  padding-right: 2px;
  box-sizing: border-box;
}

#__error .stack-frame:first-child {
  border-top: 1px solid;
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

#__error .file {
  display: table-cell;
  background: #EEE;
  padding: 5px 15px;
  width: 136px;
  text-align: right;
  border-right: 1px solid #DDD;
}

#__error .line {
  border-right: 1px solid #DDD;
  display: table-cell;
  background: #EEE;
  padding: 5px 15px;
  width: 36px;
  text-align: right;
}

#__error .__call {
  display: table-cell;
  color: #333;
  padding: 5px 10px;
  background: #FFF;
  white-space: normal;
}

#__error .__call > a {
  float: right;
  margin-left: 10px;
}

#__error a.__btn {
  text-decoration: none;
  background: #EEE;
  border: 1px solid #CCC;
  color: #666;
  font-family: sans-serif;
  width: 70px;
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
  overflow: auto;
  max-height: 220px;
  white-space: nowrap;
}

.__show #__trace {
  display: block;
}

#__error .class {
  color: #A00;
}

#__error .fn {
  color: #08A;
}

#__error .string {
  color: #C90;
}

#__error .type {
  color: #5A5;
}

#__error .info {
  cursor: help;
  color: #5A5;
}

#__error .tag {
  color: #ffd064;
}

#__error .tag-hilight {
  color: #5ed4e4;
}

#__error .error-location {
  padding: 20px;
  border-top: 1px solid #BF3D27;
  font-family: menlo, monospace;
  font-size: 12px;
  background: #FFF;
}

#__error .__location {
  display: none;
  color: #999;
  font-size: 10px;
  padding: 5px 10px;
  margin: 5px -10px -5px -10px;
}

.__message h3 {
  font-size: 16px;
  margin: -4px 0 20px;
}

.__message h3 b {
  color: #A00;
}

.__message code {
  overflow: auto;
  max-height: 160px;
  background: #3C3F41;
  color: #EEE;
  display: block;
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

.__message table {
  font-size: inherit;
  border-spacing: 0;
  line-height: 1.2;
  margin: 15px 0;
}

.__message table td {
  padding: 0 15px 0 0;
}

.__message table th, dt {
  font-weight: normal;
  color: #08A;
  text-align: left;
  padding-right: 10px;
}

</style>
    <?php
  }

}
