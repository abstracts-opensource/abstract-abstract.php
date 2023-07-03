<?php

namespace X\Markup\Xsl;

class XslValidation {

  public static $reserved = [
    'apply-imports',
    'apply-templates',
    'attribute',
    'attribute-set',
    'call-template',
    'choose',
    'comment',
    'copy',
    'copy-of',
    'decimal-format',
    'element',
    'fallback',
    'for-each',
    'if',
    'import',
    'include',
    'key',
    'message',
    'namespace-alias',
    'number',
    'otherwise',
    'output',
    'param',
    'preserve-space',
    'processing-instruction',
    'sort',
    'strip-space',
    'stylesheet',
    'template',
    'text',
    'transform',
    'value-of',
    'variable',
    'when',
    'with-param'
  ];

  public static function tag($name = null) {
    $result = static::key($name);
    return $result;
  }

  public static function key($name = null) {
    $result = $name;
    if (in_array($name, static::$reserved)) {
      $result = 'xsl:' . $name;
      if ($name === 'foreach') {
        $result = 'xsl:for-each';
      } elseif ($name === 'valueof') {
        $result = 'xsl:value-of';
      }
    }
    return $result;
  }

  public static function attribute($value) {
    $result = $value;
    if (empty($value)) {
      $value = 'true';
    }
    return $result;
  }

  public static function value($value) {
    $result = $value;
    return $result;
  }
}
