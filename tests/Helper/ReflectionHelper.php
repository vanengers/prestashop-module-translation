<?php

namespace Vanengers\PrestashopModuleTranslation\Tests\Helper;

use ReflectionClass;
use ReflectionObject;

class ReflectionHelper
{
    public static function getProperty($object, $property)
    {
        $reflectedClass = new ReflectionObject($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }

    public static function setMethodAccessToPublic($object, $method)
    {
        $reflectedClass = new ReflectionObject($object);
        $reflection = $reflectedClass->getMethod($method);
        $reflection->setAccessible(true);
        return $reflection;
    }

    public static function setProperty($object, $property, $value)
    {
        $reflectedClass = new ReflectionObject($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}