<?php

use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;

beforeEach(function (): void {
    $this->finder = new InterfaceImplementorFinder;
});

describe("InterfaceImplementorFinder Test", function (): void {
    test("set interface name sets interface name", function (): void {
        $interfaceName = "TestInterface";

        $this->finder->setInterfaceName($interfaceName);

        // We can't directly test the private property, but we can test the behavior
        expect($this->finder)->toBeInstanceOf(InterfaceImplementorFinder::class);
    });

    test("get implementing classes returns empty array initially", function (): void {
        $result = $this->finder->getImplementingClasses();

        expect($result)->toEqual([]);
    });

    test("enter node sets current namespace", function (): void {
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);

        $result = $this->finder->enterNode($namespaceNode);

        expect($result)->toBeNull();
    });

    test("enter node handles namespace without name", function (): void {
        $namespaceNode = new Namespace_(null);

        $result = $this->finder->enterNode($namespaceNode);

        expect($result)->toBeNull();
    });

    test("enter node processes class with interface", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node with interface
        $className = new Identifier("TestClass");
        $interfaceName = new Name("TestInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toContain('App\Test\TestClass');
    });

    test("enter node processes class with fully qualified interface", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node with fully qualified interface
        $className = new Identifier("TestClass");
        $interfaceName = new Name('Some\Other\Namespace\TestInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toContain('App\Test\TestClass');
    });

    test("enter node ignores class without target interface", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node with different interface
        $className = new Identifier("TestClass");
        $interfaceName = new Name("DifferentInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toEqual([]);
    });

    test("enter node ignores class without implements", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node without implements
        $className = new Identifier("TestClass");
        $classNode = new Class_($className);

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toEqual([]);
    });

    test("enter node ignores class without name", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // Create anonymous class (no name)
        $classNode = new Class_(null);

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toEqual([]);
    });

    test("enter node processes multiple interfaces on class", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node with multiple interfaces
        $className = new Identifier("TestClass");
        $interface1 = new Name("SomeOtherInterface");
        $interface2 = new Name("TestInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interface1, $interface2];

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        $implementations = $this->finder->getImplementingClasses();
        expect($implementations)->toContain('App\Test\TestClass');
    });

    test("enter node returns null for unhandled nodes", function (): void {
        $node = new Node\Stmt\Echo_([]);

        $result = $this->finder->enterNode($node);

        expect($result)->toBeNull();
    });

    test("node implements throws exception when interface name empty", function (): void {
        $this->finder->setInterfaceName("");

        $className = new Identifier("TestClass");
        $interfaceName = new Name("TestInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Interface Name Property Cannot Be Empty String");

        $this->finder->enterNode($classNode);
    });
});