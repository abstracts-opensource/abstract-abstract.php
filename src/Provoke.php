<?php

namespace X;

use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;

class Provoke {

  public static function class($name, array $arguments = []) {
    $reflection = new ReflectionClass($name);
    return $reflection->newInstanceArgs(
      static::pass(null, $name, $arguments, __FUNCTION__)
    );
  }

  public static function method($class, $name, array $arguments = []) {
    return call_user_func_array(
      array($class, $name),
      static::pass($class, $name, $arguments, __FUNCTION__)
    );
  }

  public static function function($name, array $arguments = []) {
    return call_user_func_array(
      $name,
      static::pass(null, $name, $arguments, __FUNCTION__)
    );
  }

  public static function isStaticMethod($class, $name) {
    $reflection = new ReflectionMethod($class, $name);
    if ($reflection->isStatic()) {
      return true;
    } else {
      return false;
    }
  }

  public static function pass($objectOrMethod, $name, array $arguments, $type) {

    $variables = null;

    if ($type === 'class') {
      $reflection = new ReflectionClass($name);
      $constructor = $reflection->getConstructor();
      $parameters = [];
      if (!empty($constructor)) {
        $parameters = $constructor->getParameters();
      }
    } elseif ($type === 'method') {
      $reflection = new ReflectionMethod($objectOrMethod, $name);
      $parameters = [];
      if (!empty($reflection) && count($reflection->getParameters())) {
        $parameters = $reflection->getParameters();
      }
    } elseif ($type === 'function') {
      $reflection = new ReflectionFunction($name);
      $parameters = [];
      if (!empty($reflection) && count($reflection->getParameters())) {
        $parameters = $reflection->getParameters();
      }
    }

    $variables = array_map(function ($parameter, $arguments) {
      extract($arguments);
      $defaultValue = null;
      if ($parameter->isOptional()) {
        $defaultValue = $parameter->getDefaultValue();
      }
      return isset($arguments[$parameter->getName()]) ? $arguments[$parameter->getName()] : $defaultValue;
    }, $parameters, array_fill(0, count($parameters), $arguments));

    return $variables;
  }
}
