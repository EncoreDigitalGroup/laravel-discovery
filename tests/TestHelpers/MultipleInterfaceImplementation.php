<?php

namespace Tests\TestHelpers;

class MultipleInterfaceImplementation implements TestInterface, AnotherTestInterface
{
    public function testMethod(): void
    {
        // Test implementation
    }

    public function anotherMethod(): string
    {
        return 'test';
    }
}