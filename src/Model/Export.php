<?php

namespace X\Model;

use \X\Annotations\Name;

use \X\Model\Import;

use \X\Markup\Aml\AmlValidation;
use \X\Markup\Html\HtmlValidation;
use \X\Markup\Xml\XmlValidation;
use \X\Markup\Xsl\XslValidation;

class Export {

  public $open = '';
  public $close = '';
  public $value = '';

  public static function asMarkup(
    $type = 'html',
    $data = [],
    $parent = null
  ) {

    // Normalize model to array
    if (!empty($data) && is_object($data)) {
      $data = (array)$data;
    }

    $isList = is_array($data) && array_is_list($data);

    $result = implode(
      '',
      array_map(
        function ($value, $key) use ($type, $isList, $parent) {

          // Normalize value to array
          if (!empty($value) && is_object($value)) {
            $value = (array)$value;
          }

          $node = '';
          if ($key !== '@attributes' && $key !== Name::$key . '-attributes') {

            // Handle tag
            $name = '';
            $attributes = [];
            if (!$isList) {
              // Use self key as tag name when value is not sequential
              if (!(is_array($value) && array_is_list($value))) {
                $name = $key;
                $attributes = Import::attributes($value);
              }
            } elseif (!empty($parent)) {
              // Use parent key as tag name when data is not sequential
              $name = array_key_first($parent);
              $attributes = Import::attributes($parent);
            }
            
            $tag = '';
            if ($type === 'html') {
              $tag = HtmlValidation::tag($name);
            } elseif ($type === 'aml') {
              $tag = AmlValidation::tag($name);
            } elseif ($type === 'xml') {
              $tag = XmlValidation::tag($name);
            } elseif ($type === 'xsl') {
              $tag = XslValidation::tag($name);
            }

            $open = '';
            $close = '';
            if (!empty($tag)) {
              $open = static::tag(true, $tag, $attributes, $type);
              $close = static::tag(false, $tag);
            }

            if (!empty($value)) {
              if (is_string($value)) {
                if ($type === 'html') {
                  $value = HtmlValidation::value($value);
                } elseif ($type === 'aml') {
                  $value = AmlValidation::value($value);
                } elseif ($type === 'xml') {
                  $value = XmlValidation::value($value);
                } elseif ($type === 'xsl') {
                  $value = XslValidation::value($value);
                }
              } elseif (is_array($value)) {
                $value = static::asMarkup($type, $value, [$key => $value]);
              }
            }

            $node = $open . $value . $close;
          }

          return $node;
        },
        $data,
        array_keys($data)
      )
    );

    return $result;
  }

  public static function tag(
    bool $open,
    $name,
    $attributes = null,
    $type = null
  ) {

    if (is_null($open)) {
      $open = true;
    }

    $result = '';
    $tag = $name;
    if (!empty($tag)) {
      if ($open) {
        $result =
          '<'
          . $name
          . (!empty($attributes) ?
            ' ' . implode(
              ' ',
              // Stringify attributes
              array_map(function ($value, $key) use ($type) {
                $string = $key;
                if ($type === 'html') {
                  $value = HtmlValidation::attribute($value);
                } elseif ($type === 'aml') {
                  $value = AmlValidation::attribute($value);
                } elseif ($type === 'xml') {
                  $value = XmlValidation::attribute($value);
                } elseif ($type === 'xsl') {
                  $value = XslValidation::attribute($value);
                }
                if (!empty($value)) {
                  $string = $key . '="' . $value . '"';
                }
                return $string;
              }, $attributes, array_keys($attributes))
            )
            : ''
          )
          . '>';
      } else {
        $result = '</' . $name . '>';
      }
    }
    return $result;
  }
}
