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

describe("InterfaceImplementorFinder", function (): void {
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

    test("enter node processes class with matching interface", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // Set namespace
        $namespaceName = new Name("App\Test");
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Test both simple and fully qualified interface names
        $className = new Identifier("TestClass");
        $interfaceName = new Name("TestInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $result = $this->finder->enterNode($classNode);
        expect($result)->toBeNull()
            ->and($this->finder->getImplementingClassesForInterface("TestInterface"))
            ->toContain('App\Test\TestClass');

        // Test fully qualified interface
        $className2 = new Identifier("TestClass2");
        $interfaceName2 = new Name('Some\Other\Namespace\TestInterface');
        $classNode2 = new Class_($className2);
        $classNode2->implements = [$interfaceName2];

        $this->finder->enterNode($classNode2);
        expect($this->finder->getImplementingClassesForInterface("TestInterface"))->toContain('App\Test\TestClass2');
    });

    test("enter node ignores non-matching classes", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // Set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Test class with different interface
        $classNode1 = new Class_(new Identifier("TestClass"));
        $classNode1->implements = [new Name("DifferentInterface")];

        $this->finder->enterNode($classNode1);
        expect($this->finder->getImplementingClassesForInterface("TestInterface"))->toEqual([]);

        // Test class without implements
        $classNode2 = new Class_(new Identifier("TestClass2"));
        $this->finder->enterNode($classNode2);
        expect($this->finder->getImplementingClassesForInterface("TestInterface"))->toEqual([]);

        // Test anonymous class
        $classNode3 = new Class_(null);
        $this->finder->enterNode($classNode3);
        expect($this->finder->getImplementingClassesForInterface("TestInterface"))->toEqual([]);
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
        $implementations = $this->finder->getImplementingClassesForInterface("TestInterface");
        expect($implementations)->toContain('App\Test\TestClass');
    });

    test("enter node returns null for unhandled nodes", function (): void {
        $node = new Node\Stmt\Echo_([]);

        $result = $this->finder->enterNode($node);

        expect($result)->toBeNull();
    });

    test("node implements throws exception when interface names empty", function (): void {
        // Don't set any interface names - leave array empty
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        $className = new Identifier("TestClass");
        $interfaceName = new Name("TestInterface");
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Interface Names Cannot Be Empty");

        $this->finder->enterNode($classNode);
    });

    test("enter node handles class without implements property", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        // Set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);

        // Create class node without implements
        $className = new Identifier("TestClass");
        $classNode = new Class_($className);
        // Don't set implements property at all

        $result = $this->finder->enterNode($classNode);

        expect($result)->toBeNull();
        expect($this->finder->getImplementingClassesForInterface("TestInterface"))->toEqual([]);
    });

    test("setInterfaceNames method initializes implementing classes array", function (): void {
        $interfaces = ["Interface1", "Interface2", "Interface3"];
        $this->finder->setInterfaceNames($interfaces);

        foreach ($interfaces as $interface) {
            expect($this->finder->getImplementingClassesForInterface($interface))->toEqual([]);
        }
    });

    test("getImplementingClassesForInterface returns empty array for unknown interface", function (): void {
        $this->finder->setInterfaceName("TestInterface");

        expect($this->finder->getImplementingClassesForInterface("UnknownInterface"))->toEqual([]);
    });
});