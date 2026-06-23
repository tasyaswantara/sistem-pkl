<?php

namespace Tests\Support;

use ReflectionMethod;

trait InvokesPrivateMethods
{
    protected function invokePrivate(object $object, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}
