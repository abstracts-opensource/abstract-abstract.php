<?php

namespace X;

use \X\Annotations\Name;

use \X\Model\Import;
use \X\Model\Export;

use \X\Model\Aml\AmlModel;
use \X\Model\Xml\XmlModel;
use \X\Model\Xsl\XslModel;

use \X\Markup\Html\HtmlValidation;
use \X\Markup\Xml\XmlValidation;
use \X\Markup\Xsl\XslValidation;

use \X\Naming;
use \X\Provoke;

use DOMDocument;
use XSLTProcessor;

/**
 * Abstract Processor
 *
 * @param  string  $abstract
 * @param  string  $type ('html', 'xml', 'text', 'binary', 'base64')
 */
class Processor {

  public $data;
  public $model;
  public $content;

  private static $namespacesCached = [];

  public function __construct($data = null, $htmlValidate = true) {

    $this->data = $data;

    $object = new AmlModel($data);
    $this->model = $object->model;

    // var_dump((array)$this->model);

    $content = $this->compile($this->model, $htmlValidate);
    // var_dump($content);

    // echo $this->model->asAML();
    // echo "\n";
  }

  function compile(
    $data = [],
    $htmlValidate = false,
    $parent = null,
    $namespaces = []
  ) {

    // Normalize model to array
    if (!empty($data) && is_object($data)) {
      $data = (array)$data;
    }

    $isList = is_array($data) && array_is_list($data);

    $result = implode(
      '',
      array_map(
        function ($value, $key) use ($htmlValidate, $isList, $parent, $namespaces) {

          // Normalize value to array
          if (!empty($value) && is_object($value)) {
            $value = (array)$value;
          }

          $node = '';
          if ($key !== '@attributes' && $key !== Name::$key . '-attributes') {

            $function = Naming::key($key);

            // handle parent as class
            $namespace = '';
            $constructers = [];
            if (!empty($parent)) {
              // retrieve available namespaces
              $namespaces = static::namespaces(static::namespace($namespaces));
              if (!empty($namespaces)) {
                $namespace = end($namespaces);
                $constructers = Import::attributes($parent);
              }
            }


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

            // Handle tag
            $tag = $name;
            $tag = HtmlValidation::tag($name, $htmlValidate);

            // handle interface rules
            $isHTML = !empty(HtmlValidation::tag($name, true));
            $ignore = array_key_exists('ignore', $attributes);
            $source = [];
            if (array_key_exists('source', $attributes)) {
              $source = explode('|', $attributes['source']);
            }
            if (!$ignore) {

              if (
                !is_numeric($key)
                && !in_array($key, XslValidation::$reserved)
                && (!$isHTML
                  || in_array('method', $source)
                )
              ) {

                $cached = static::getCache($namespace, $attributes);
                if (!empty($cached)) {
                  // use cache if cached
                  array_push($results, $cached[0]['class']);
                }

                if (!empty($namespace)) {
                  if (method_exists($namespace, $function)) {
                    if (Provoke::isStaticMethod($namespace, $function)) {
                      // Provoke::method($namespace, $name);
                    } else {
                      Provoke::class($namespace);
                    }
                    // if (array_key_exists('as', $attributes)) {
                    //   $variables[$attributes['as']] = Execution::method($class, $function, $attributes);
                    // }
                    $isHTML = false;
                  }
                }

                array_push($namespaces, $key);
              }
            }

            $open = '';
            $close = '';
            if (!empty($tag)) {
              $open = Export::tag(true, $tag, $attributes, ($htmlValidate ? 'html' : 'xml'));
              $close = Export::tag(false, $tag);
            }

            if (is_string($value)) {
              if ($htmlValidate) {
                $value = HtmlValidation::value($value);
              }
            } elseif (is_array($value)) {
              $value = $this->compile($value, $htmlValidate, [$key => $value], $namespaces);
            } else {
              $value = '';
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

  private static function namespace($keys) {
    return '\\' . implode(
      '\\',
      array_map(
        function ($key) {
          return implode(
            '\\',
            array_map(
              function ($part) {
                return Naming::class($part);
              },
              explode('-', $key)
            )
          );
        },
        $keys
      )
    );
  }

  private static function namespaces($namespace) {

    $results = [];

    // reduce namespace for recursive
    $names = is_array($namespace) ? $namespace : explode('\\', $namespace);
    array_shift($names);

    if (!empty($names)) {

      // format namespace to check
      $namespace = '\\' . trim(implode('\\', $names), '\\');

      if (class_exists($namespace)) {
        // collect result if namespace exists
        array_push($results, $namespace);
      }

      // recursively classify to search for available classes
      array_merge($results, static::namespaces($names));

    }

    return $results;
  }

  private static function getCache($namespace, $attributs) {
    $result = [];
    if (!empty($namespace)) {

      $result = array_filter(
        static::$namespacesCached,
        function ($cached) use ($namespace, $attributs) {
          if (
            $cached['namespace'] === $namespace
            && static::compareCache($cached['attributes'], $attributs)
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

  private static function compareCache($a, $b) {
    if (count($a) != count($b)) {
      return false;
    }
    foreach ($a as $key => $value) {
      if (is_object($value)) {
        $value = (array)$value;
      }
      if (is_array($value)) {
        if (!static::compareCache($value, $b[$key])) {
          return false;
        }
      } else {
        if (!isset($b[$key]) || $b[$key] !== $value) {
          return false;
        }
      }
    }
    return true;
  }

  public function merge($optimize = true) {

    // // Load the XML file
    $contentXML = Resource::get('src/resources/abstract/app/test.xml');
    $modelXML = new XmlModel($contentXML);

    // // Load the XSLT stylesheet
    $contentXSL = Resource::get('src/resources/abstract/app/test.xsl');
    $modelXSL = new XslModel($contentXSL);

    $xml = $modelXML->asXML();
    $xsl = $modelXSL->asXSL();

    // Create a DOMDocument object for the XML
    $xmlDoc = new DOMDocument();
    $xmlDoc->loadXML($xml);

    // Create a DOMDocument object for the XSLT
    $xslDoc = new DOMDocument();
    $xslDoc->loadXML($xsl);

    // Create an XSLT processor
    $processor = new XSLTProcessor();
    $processor->importStylesheet($xslDoc);

    // Transform the XML using XSLT and get the result
    $doc = $processor->transformToDoc($xmlDoc);
    if (!$optimize) {
      $doc->preserveWhiteSpace = false;
      $doc->formatOutput = true;
    }
    $output = $doc->saveHTML();
    // $output = $processor->transformToXML($xmlDoc);

    echo $output;
  }

  // private static function namespace($key) {
  //   return '\\' . implode(
  //     '\\',
  //     array_map(
  //       function ($part) {
  //         return Name::class($part);
  //       },
  //       explode('-', $key)
  //     )
  //   );
  // }
}
