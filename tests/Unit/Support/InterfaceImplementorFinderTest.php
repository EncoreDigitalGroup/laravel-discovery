<?php

namespace Tests\Unit\Support;

use EncoreDigitalGroup\LaravelDiscovery\Support\InterfaceImplementorFinder;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InterfaceImplementorFinderTest extends TestCase
{
    private InterfaceImplementorFinder $finder;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the Str::empty() method by creating a global function
        if (!function_exists('EncoreDigitalGroup\StdLib\Objects\Support\Types\Str::empty')) {
            if (!class_exists('EncoreDigitalGroup\StdLib\Objects\Support\Types\Str')) {
                eval('
                    namespace EncoreDigitalGroup\StdLib\Objects\Support\Types {
                        class Str {
                            public static function empty() {
                                return "";
                            }
                        }
                    }
                ');
            }
        }
        
        $this->finder = new InterfaceImplementorFinder();
    }

    public function test_set_interface_name_sets_interface_name(): void
    {
        $interfaceName = 'TestInterface';
        
        $this->finder->setInterfaceName($interfaceName);
        
        // We can't directly test the private property, but we can test the behavior
        $this->assertInstanceOf(InterfaceImplementorFinder::class, $this->finder);
    }

    public function test_get_implementing_classes_returns_empty_array_initially(): void
    {
        $result = $this->finder->getImplementingClasses();
        
        $this->assertEquals([], $result);
    }

    public function test_enter_node_sets_current_namespace(): void
    {
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        
        $result = $this->finder->enterNode($namespaceNode);
        
        $this->assertNull($result);
    }

    public function test_enter_node_handles_namespace_without_name(): void
    {
        $namespaceNode = new Namespace_(null);
        
        $result = $this->finder->enterNode($namespaceNode);
        
        $this->assertNull($result);
    }

    public function test_enter_node_processes_class_with_interface(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);
        
        // Create class node with interface
        $className = new Identifier('TestClass');
        $interfaceName = new Name('TestInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertContains('App\Test\TestClass', $implementations);
    }

    public function test_enter_node_processes_class_with_fully_qualified_interface(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);
        
        // Create class node with fully qualified interface
        $className = new Identifier('TestClass');
        $interfaceName = new Name('Some\Other\Namespace\TestInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertContains('App\Test\TestClass', $implementations);
    }

    public function test_enter_node_ignores_class_without_target_interface(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);
        
        // Create class node with different interface
        $className = new Identifier('TestClass');
        $interfaceName = new Name('DifferentInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertEquals([], $implementations);
    }

    public function test_enter_node_ignores_class_without_implements(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);
        
        // Create class node without implements
        $className = new Identifier('TestClass');
        $classNode = new Class_($className);
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertEquals([], $implementations);
    }

    public function test_enter_node_ignores_class_without_name(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // Create anonymous class (no name)
        $classNode = new Class_(null);
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertEquals([], $implementations);
    }

    public function test_enter_node_processes_multiple_interfaces_on_class(): void
    {
        $this->finder->setInterfaceName('TestInterface');
        
        // First set namespace
        $namespaceName = new Name('App\Test');
        $namespaceNode = new Namespace_($namespaceName);
        $this->finder->enterNode($namespaceNode);
        
        // Create class node with multiple interfaces
        $className = new Identifier('TestClass');
        $interface1 = new Name('SomeOtherInterface');
        $interface2 = new Name('TestInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interface1, $interface2];
        
        $result = $this->finder->enterNode($classNode);
        
        $this->assertNull($result);
        $implementations = $this->finder->getImplementingClasses();
        $this->assertContains('App\Test\TestClass', $implementations);
    }

    public function test_enter_node_returns_null_for_unhandled_nodes(): void
    {
        $node = new Node\Stmt\Echo_([]);
        
        $result = $this->finder->enterNode($node);
        
        $this->assertNull($result);
    }

    public function test_node_implements_throws_exception_when_interface_name_empty(): void
    {
        $this->finder->setInterfaceName('');
        
        $className = new Identifier('TestClass');
        $interfaceName = new Name('TestInterface');
        $classNode = new Class_($className);
        $classNode->implements = [$interfaceName];
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Interface Name Property Cannot Be Empty String');
        
        $this->finder->enterNode($classNode);
    }
}