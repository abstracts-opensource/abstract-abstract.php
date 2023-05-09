<?php

namespace Ab;

use \Ab\Interfacer;
use \Ab\Transformer;

use \Ab\Utilities\Compare;
use \Ab\Utilities\Caller;

class Compiler {

  private static $namespacesCached = [];

  private static $singleTags = [
    'br',
    'meta'
  ];

  /**
   * Compile Abstract interface
   *
   * @param  string  $value
   */
  public static function compile(
    $node,
    bool $asXML = true,
    $parent = null,
    $variables = [],
    $root = '',
    $independent = false
  ) {

    $content = '';

    if (!is_object($node)) {
      $node = Interfacer::read($node, true, $asXML);
    }

    if (!empty($node) && is_object($node)) {

      // read root node members
      foreach ($node as $key => $value) {

        $inheritance = '';

        // handle parent as class
        $class = null;
        $namespace = '';
        if (!empty($parent)) {
          // transform tag name to namespace
          $namespace = $root . static::namespace(key($parent));
          // retrieve class
          $class = static::classify($namespace, static::attributes($parent));
        }

        // retrieve attributes
        $attributes = static::attributes($value);

        // handle interface rules
        $isHTML = Interfacer::is_valid($key);
        $ignore = array_key_exists('ignore', $attributes);
        if (!$ignore) {
          // transform tag name to namespace
          if (class_exists(static::namespace($key))) {
            $isHTML = false;
          }
        }

        // call method or function and set to variables
        $function = Transformer::key($key);
        if (!empty($class)) {
          if (method_exists(get_class($class), $function)) {
            if (array_key_exists('as', $attributes)) {
              $variables[$attributes['as']] = Caller::method($class, $function, $attributes);
            }
            $isHTML = false;
          }
        } else {
          if (function_exists($function)) {
            if (array_key_exists('as', $attributes)) {
              $variables[$attributes['as']] = Caller::function($function, $attributes);
            }
            $isHTML = false;
          }
        }

        // handle hierarchical namespace
        if (!$isHTML) {
          if (empty($class) || !function_exists($function) || !method_exists($namespace, $function)) {
            if (!empty($value) && !$independent) {
              $inheritance = $namespace;
            }
          }
        }

        // write content
        if (array_key_exists('map', $attributes)) {
          $valueAs = '';
          $keyAs = '';
          $indexAs = '';
          if (array_key_exists('as', $attributes)) {
            $valueAs = $attributes['as'];
            unset($attributes['as']);
          }
          if (array_key_exists('key', $attributes)) {
            $keyAs = $attributes['key'];
            unset($attributes['key']);
          }
          if (array_key_exists('index', $attributes)) {
            $indexAs = $attributes['index'];
            unset($attributes['index']);
          }
          $valueAs = $valueAs;
          $keyAs = $keyAs;
          $indexAs = $indexAs;
          $condition = static::assign($attributes['map'], $variables);
          unset($attributes['map']);
          $map = "array_map(
            function (\$forValue, \$forKey, \$forIndex) use (
              \$key,
              \$value,
              \$attributes,
              \$variables,
              \$isHTML,
              \$asXML,
              \$inheritance,
              \$valueAs,
              \$keyAs,
              \$indexAs
            ) {
              if (!empty(\$valueAs)) {
                \$variables[\$valueAs] = \$forValue;
              }
              if (!empty(\$keyAs)) {
                \$variables[\$keyAs] = \$forKey;
              }
              if (!empty(\$indexAs)) {
                \$variables[\$indexAs] = \$forIndex;
              }
              return static::write(
                \$key,
                \$value,
                \$attributes,
                \$variables,
                (is_object(\$value) ?
                  static::compile(
                    \$value,
                    \$asXML,
                    (object)[\$key => \$value],
                    \$variables,
                    \$inheritance,
                    \$isHTML
                  ) : ''
                ),
                \$isHTML,
                \$asXML,
                \$inheritance
              );
              return '';
            },
            " . $condition . ",
            array_keys(" . $condition . "),
            array_keys(array_values(" . $condition . "))
          )";
          $content .= eval("return implode('', " . $map . ");");
        } elseif (array_key_exists('if', $attributes)) {
          $condition = static::assign($attributes['if'], $variables);
          unset($attributes['if']);
          $written = static::write(
            $key,
            $value,
            $attributes,
            $variables,
            (is_object($value) ?
              static::compile(
                $value,
                $asXML,
                (object)[$key => $value],
                $variables,
                $inheritance,
                $isHTML
              ) : ''
            ),
            $isHTML,
            $asXML,
            $inheritance
          );
          $content .= eval("return ((" . $condition . ") ? '" . $written . "' : '');");
        } else {
          $content .= static::write(
            $key,
            $value,
            $attributes,
            $variables,
            (is_object($value) ?
              static::compile(
                $value,
                $asXML,
                (object)[$key => $value],
                $variables,
                $inheritance,
                $isHTML
              ) : ''
            ),
            $isHTML,
            $asXML,
            $inheritance
          );
        }
      }
    }
    return $content;
  }

  private static function write(
    $key,
    $value,
    $attributes = [],
    $variables = [],
    $children = '',
    bool $isHTML = false,
    bool $asXML = true,
    bool $inheritance = false
  ) {
    $content = '';
    // write opening tag to content
    if ($isHTML && $asXML) {
      $attributesXML = static::attributesXML($attributes);
      if (in_array($key, static::$singleTags)) {
        $content .= '<' . $key . $attributesXML . ' />';
      } else {
        $content .= '<' . $key . $attributesXML . '>';
      }
    } else {
      if ($asXML && $key !== 'encapsulated') {
        $content .= '<!--' . $key . '-->';
      }
    }
    // write value inside tag to content
    if (is_array($value)) {
      foreach ($value as $childKey => $childValue) {
        if (is_int($childKey)) {
          $content .= static::express((string)$childValue, $variables);
        }
      }
    } else {
      $content .= static::express((string)$value, $variables);
    }
    // write value of children recursively to content
    $content .= $children;
    // write closing tag to content
    if ($isHTML && $asXML) {
      if (!in_array($key, static::$singleTags)) {
        $content .= '</' . $key . '>';
      }
    }
    return $content;
  }

  private static function express($string, $variables = []): string {
    // replace strings inside double curly braces
    $result = preg_replace_callback(
      '/{{\s*(.*)\s*}}/',
      function ($matches) use ($variables) {
        $result = (isset($matches[0]) ? $matches[0] : '');
        if (isset($matches[1])) {
          preg_match('/{{\s*(.*)\s*}}/', $matches[1], $innerMatches);
          $result = static::assign(
            (count($innerMatches) ? static::express($matches[1], $variables) : $matches[1]),
            $variables
          );
          $result = preg_replace_callback(
            '/(\+)\s*(?![\s\d]+)/',
            function($matches) {
              return str_replace('+', '.', $matches[0]);
            },
            $result
          );
        }
        // evaluate expression to real value
        return eval("return " . (string)$result . ";");
      },
      $string
    );
    return $result;
  }

  private static function assign($string, $variables = [], $associative = true): string {
    // replace variable representors
    $result = preg_replace_callback(
      '/([a-zA-Z_][a-zA-Z0-9_(->)]*)/',
      function ($matches) use ($variables, $associative) {
        $variables = $variables;
        $value = '';
        if (isset($matches[1])) {
          $compat = preg_replace_callback(
            '/(\.)(?![\"\'])(\w+)/',
            function ($JSObjectMatches) {
              return str_replace('.', '->', $JSObjectMatches[0]);
            },
            $matches[1]
          );
          $partsArray = explode('[', $compat);
          $partsObject = explode('->', $compat);
          $condition = "\$variables['" . $compat . "']";
          if (strpos($partsArray[0], '->') === false && count($partsArray) > 0) {
            $condition = preg_replace('/' . $partsArray[0] . '/', "\$variables['" . $partsArray[0] . "']", $compat, 1);
          } else if (strpos($partsObject[0], '[') === false && count($partsObject) > 0) {
            $condition = preg_replace('/' . $partsObject[0] . '/', "\$variables['" . $partsObject[0] . "']", $compat, 1);
          }
          $value = eval("return " . $condition . ";");
        }
        // format value of variable
        $result = is_string($value) ?
          "'" . $value . "'"
          : (is_array($value) || is_object($value) ?
            ($associative ? var_export($value, true) : '')
            : (is_bool($value) ?
              ($value ? 'true' : 'false')
              : (is_null($value) ? '' : (string)$value)
            )
          );
        return $result;
      },
      $string
    );
    return $result;
  }

  private static function classify($namespace, $attributes = []) {

    $result = null;

    // check cache
    $cached = static::getCache($namespace, $attributes);
    if (!empty($cached)) {
      // use cache if cached
      $result = $cached[0]['class'];
    } else {
      // normalize namespace to partial namespace
      $parts = $namespace;
      if (!is_array($namespace)) {
        $parts = explode('\\', $namespace);
      }
      // reduce namespace for recursive
      $reduces = $parts;
      array_shift($reduces);

      // format namespace to check
      $namespace = '\\' . trim(implode('\\', $parts), '\\');
      if (class_exists($namespace)) {
        $result = Caller::class($namespace, $attributes);
        if (!empty($result)) {
          // set cache
          static::setCache($parts, $result, $attributes);
        }
      } else {
        // recursively classify to call matched class
        if (!empty($reduces)) {
          $result = static::classify($reduces, $attributes);
        }
      }
    }

    return $result;
  }

  private static function objectify($expression, $variables = [], $depth = 0) {

    $result = $variables;
    if (empty($depth)) {
      $result = '';
    }

    // normalize expression to partial expression
    $parts = $expression;
    if (!is_array($expression)) {
      $parts = explode('->', $expression);
    }
    // reduce expression for recursive
    $reduces = $parts;
    array_shift($reduces);

    // map expression with vairables
    if (isset($parts[0]) && !empty($parts[0])) {
      if (is_object($variables) && !empty((array)$variables)) {
        $key = $parts[0];
        if (isset($variables->$key)) {
          $result = static::objectify($reduces, $variables->$key, $depth + 1);
        }
      } else if (is_array($variables)) {
        if (array_key_exists($parts[0], $variables) && !empty($variables)) {
          $result = static::objectify($reduces, $variables[$parts[0]], $depth + 1);
        }
      }
    }

    return $result;
  }

  private static function namespace($key) {
    return '\\' . implode(
      '\\',
      array_map(
        function ($part) {
          return Transformer::class($part);
        },
        explode('-', $key)
      )
    );
  }

  private static function attributes($value = [], $variables = []) {
    $attributes = [];
    $array = (array)$value;
    if (array_key_exists('@attributes', $array)) {
      // map attributes expressions with value
      $attributes = array_map(function ($attributeValue) use ($variables) {
        return static::express((string)$attributeValue, $variables);
      }, $array['@attributes']);
    }
    return (array)$attributes;
  }

  private static function attributesXML($attributes = []): string {
    $strings = '';
    if (!empty($attributes)) {
      $strings = implode(
        ' ',
        // map attributes with strings
        array_map(function ($value, $key) {
          $string = $key;
          if (!empty($value) || $value === 'true') {
            $string = $key . '="' . $value . '"';
          }
          return $string;
        }, $attributes, array_keys($attributes))
      );
    }
    return (!empty($strings) ? ' ' . $strings : '');
  }

  private static function getCache($namespace, $attributs) {
    $result = [];
    if (!empty($namespace)) {
      $result = array_filter(
        static::$namespacesCached,
        function ($cached) use ($namespace, $attributs) {
          if (
            $cached['namespace'] === $namespace
            && Compare::arrays($cached['attributes'], $attributs)
          ) {
            return true;
          }
          return false;
        }
      );
    }
    sort($result);
    return (!empty($result) ? $result : []);
  }

  private static function setCache($namespace, $class, $attributes) {
    $result = false;
    if (!empty($namespace) && !empty($class)) {
      array_push(
        static::$namespacesCached,
        [
          'namespace' => $namespace,
          'class' => $class,
          'attributes' => $attributes
        ]
      );
    }
    return $result;
  }

  function html($xml, bool $newline = false) {

    // Replace custom tags with data attributes
    $html = preg_replace('/<(\w+)([^>]*)>/', '<div data-$1$2>', $xml);
    $html = preg_replace('/<\/(\w+)>/', '</div>', $html);

    // Replace tag name with lowercase
    $html = preg_replace_callback('/<div data-(\w+)([^>]*)>/', function ($matches) {
      return "<div data-" . strtolower($matches[1]) . $matches[2] . ">";
    }, $html);

    return $html;
  }
}
