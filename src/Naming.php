<?php

namespace X;

class Naming {

  public static function class($key) {
    // if (empty($key)) {
    //   return '';
    // }
    // $formatted = str_replace('_', '-', static::key($key));
    // $formatted = ucwords($formatted, '-');
    // $formatted = str_replace('-', '', $formatted);
    
    // return $formatted;
    
    $parts = preg_split('/[\s-]+/', $key);
    $result = '';

    foreach ($parts as $part) {
        $result .= ucfirst($part);
    }

    return $result;
  }

  public static function key($text, $delimiter = '_') {
    // $text = strip_tags($text);
    // $text = preg_replace('/&.+?;/', '', $text);
    // $text = urldecode($text);
    // $text = mb_strtolower($text, 'UTF-8');
    // $text = preg_replace('/[^a-z0-9]+/i', $delimiter, $text);
    // $text = preg_replace('/(' . preg_quote($delimiter, '/') . '){2,}/', $delimiter, $text);
    // $text = trim($text, $delimiter);
    // return $text;

    // $result = strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . $delimiter . '$2', $text));

    // return $result;

    $words = preg_split('/(?=[A-Z])/', $text);
    $words = array_map('strtolower', $words);
    $words = array_filter($words);

    return implode($delimiter, $words);

    // $words = preg_split('/(?=[A-Z_])|_/', $text);
    // $words = array_filter($words);

    // return implode($delimiter, $words);
  }

}