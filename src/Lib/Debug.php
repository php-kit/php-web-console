<?php
namespace PhpKit\WebConsole\Lib;

use Exception;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Log\LoggerInterface;

class Debug
{
  const RAW_TEXT = '*RAW*';

  /**
   * Returns a formatted HTML span showing the given class name without the namespace part, but it also including the
   * namespace via a tooltip.
   *
   * @param string $name
   * @return string
   */
  static function formatClassName ($name)
  {
    $n = explode ('\\', $name);
    $c = array_pop ($n);
    return sprintf ("<span class='__type hint--rounded hint--top' data-hint='%s'>%s</span>", $name, $c);
  }

  /**
   * @param mixed $v
   * @return string
   */
  public static function getType ($v)
  {
    if (is_object ($v)) {
      $c = get_class ($v);
      return sprintf ('%s<sup><i>%d</i></sup>', self::shortenType ($c), self::objectId ($v));
    }
    if (is_array ($v))
      return 'array(' . count (array_keys ($v)) . ')';
    if (is_null ($v))
      return 'null';

    return gettype ($v);
  }

  /**
   * Generates a table with a header column and a value column from the given array.
   *
   * @param mixed  $value
   * @param string $title        [optional]
   * @param int    $maxDepth     [optional] Max. recursion depth.
   * @param array  $excludeProps [optional] Exclude properties whose name is on the list.
   * @param bool   $excludeEmpty [optional] Exclude empty properties.
   * @param int    $depth        [optional] For internal use.
   * @return string
   */
  public static function grid ($value, $title = '', $maxDepth = 1, $excludeProps = [], $excludeEmpty = false,
                               $depth = 0)
  {
    if (is_null ($value) || is_scalar ($value))
      return self::toString ($value);
    if ($depth >= $maxDepth)
      return "<i>(...)</i>";

    if (is_object ($value)) {
      if (method_exists ($value, '__debugInfo'))
        $value = $value->__debugInfo ();
      else $value = get_object_vars ($value);
    }
    if ($title) $title = "<p><b>$title</b></p>";

    // Exclude some properties ($excludeProps) from $value.
    $value = array_diff_key ($value, array_fill_keys ($excludeProps, false));
    if ($excludeEmpty)
      $value = array_prune_empty ($value);

    return $value ? "$title<table class=__console-table><colgroup><col width=160><col width=100%></colgroup>
" . implode ('',
        map ($value, function ($v, $k) use ($depth, $maxDepth, $excludeProps, $excludeEmpty) {
          $v = self::grid ($v, '', $maxDepth, $excludeProps, $excludeEmpty, $depth + 1);
          return "<tr><th>$k<td>$v";
        })) . "
</table>"
      : '<i>[]</i>';
  }

  /**
   * Interpolates context values into message placeholders, for use on PSR-3-compatible logging.
   *
   * @param string $message Message with optional placeholder with syntax {key}.
   * @param array  $context Array from where to fetch values corresponing to the interpolated keys.
   * @return string
   */
  public static function interpolate ($message, array $context = [])
  {
    // build a replacement array with braces around the context keys
    $replace = [];
    foreach ($context as $key => $val) {
      $replace['{' . $key . '}'] = $val;
    }
    // interpolate replacement values into the message and return
    return strtr ($message, $replace);
  }

  /**
   * Gets the base PHP namespace for this library.
   *
   * @return string
   */
  public static function libraryNamespace ()
  {
    $c = explode ('\\', get_class ());
    array_pop ($c);
    return implode ('\\', $c);
  }

  /**
   * Shortcut to log a formatted exception on the provided logger.
   *
   * @param LoggerInterface $logger
   * @param Exception       $exception
   */
  public static function logException (LoggerInterface $logger, Exception $exception)
  {
    $logger->error (sprintf ("%s, at %s(%s)", $exception->getMessage (),
      ErrorConsole::shortFileName ($exception->getFile ()), $exception->getLine ()));
  }

  /**
   * Returns an object's unique identifier (a short version), useful for debugging.
   *
   * @param object|null $o
   * @return int|string A serial number from a global inspection sequence; '' if it is null.
   */
  public static function objectId ($o)
  {
    if (is_null ($o))
      return '';
    static $ids = [];
    static $c = 0;
    $id = spl_object_hash ($o);
    if (isset($ids[$id]))
      return $ids[$id];
    return $ids[$id] = ++$c;
  }

  /**
   * @param string $c
   * @return string
   */
  public static function shortenType ($c)
  {
    $l = array_slice (explode ('\\', $c), -1)[0];
    return "<span title='$c'>$l</span>";
  }

  /**
   * @param mixed $v When it's a string, if it begins with {@see RAW_TEXT}, it is not escaped.
   * @param bool  $enhanced
   * @return string
   */
  public static function toString ($v, $enhanced = true)
  {
    if (isset($v)) {
      if ($v instanceof \Closure)
        return '<i>(native code)</i>';
      elseif (is_bool ($v))
        return $v ? '<span class=__type>true</span>' : '<span class=__type>false</span>';
      elseif (is_string ($v)) {
        $l = strlen (self::RAW_TEXT);
        return substr ($v, 0, $l) == self::RAW_TEXT ? substr ($v, $l)
          : sprintf ("<i>'</i>%s<i>'</i>", htmlspecialchars ($v));
      }
      elseif ($v instanceof \PowerString)
        return sprintf ("<i>(</i>%s<i>)'</i>%s<i>'</i>",
          $enhanced ? self::shortenType (\PowerString::class) : typeOf ($v),
          htmlspecialchars ($v));
      elseif (!is_array ($v) && !is_object ($v))
        return htmlspecialchars (str_replace ('    ', '  ', trim (print_r ($v, true))));
    }
    return $enhanced ? sprintf ('<span class=__type>%s</span>', self::getType ($v)) : typeOf ($v);
  }

  /**
   * If the argument is an object, this returns a formatted HTML span showing its class name without the namespace part,
   * but it also includes the namespace via a tooltip.
   * Other argument types are converted the same way {@see typeOf()} does, but enclosed within a `kbd` tag.
   *
   * @param mixed $x
   * @return string
   */
  static function typeInfoOf ($x)
  {
    if (is_null ($x)) return 'null';
    return is_object ($x) ? self::formatClassName (get_class ($x)) : '<span class=__type>' . typeOf ($x) . '</span>';
  }

}
