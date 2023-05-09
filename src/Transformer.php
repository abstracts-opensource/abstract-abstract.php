<?php

namespace Ab;

class Transformer {

  public static function class($key) {
    if (empty($key)) {
      return '';
    }
    $formatted = str_replace('_', '-', static::key($key));
    $formatted = ucwords($formatted, '-');
    $formatted = str_replace('-', '', $formatted);
    return $formatted;
  }

  public static function key($text, $delimiter = '_') {
    $text = strip_tags($text);
    $text = preg_replace('/&.+?;/', '', $text);
    $text = urldecode($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/i', $delimiter, $text);
    $text = preg_replace('/(' . preg_quote($delimiter, '/') . '){2,}/', $delimiter, $text);
    $text = trim($text, $delimiter);
    return $text;
  }

}