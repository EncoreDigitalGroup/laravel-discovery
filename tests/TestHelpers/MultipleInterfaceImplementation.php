<?php

namespace Tests\TestHelpers;

class MultipleInterfaceImplementation implements AnotherTestInterface, TestInterface
{
    public function testMethod(): void
    {
        // Test implementation
    }

    public function anotherMethod(): string
    {
        return "test";
    }
}