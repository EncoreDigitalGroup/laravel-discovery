<?php

/*
 * Copyright (c) 2025. Encore Digital Group.
 * All Rights Reserved.
 */

namespace EncoreDigitalGroup\LaravelDiscovery\Support;

use EncoreDigitalGroup\StdLib\Objects\Support\Types\Str;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use RuntimeException;

/** @internal */
class InterfaceImplementorFinder extends NodeVisitorAbstract
{
    private array $implementingClasses = [];
    private string $currentNamespace = "";
    private array $interfaceNames = [];

    /**
     * @deprecated Use setInterfaceNames() instead for multi-interface discovery
     */
    public function setInterfaceName(string $interfaceName): void
    {
        $this->interfaceNames = [$interfaceName];
    }

    public function setInterfaceNames(array $interfaceNames): void
    {
        $this->interfaceNames = $interfaceNames;
        // Initialize the implementing classes array for each interface
        foreach ($interfaceNames as $interfaceName) {
            if (!isset($this->implementingClasses[$interfaceName])) {
                $this->implementingClasses[$interfaceName] = [];
            }
        }
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name instanceof Name ? $node->name->toString() : "";
        } elseif ($node instanceof Node\Stmt\Class_) {
            if (!$node->name instanceof Identifier) {
                return null;
            }

            $className = $this->currentNamespace . "\\" . $node->name->toString();
            $this->nodeImplements($node, $className);
        }

        return null;
    }

    public function getImplementingClasses(): array
    {
        return $this->implementingClasses;
    }

    public function getImplementingClassesForInterface(string $interfaceName): array
    {
        return $this->implementingClasses[$interfaceName] ?? [];
    }

    private function nodeImplements(Node\Stmt\Class_ $node, string $className): void
    {
        if (empty($this->interfaceNames)) {
            throw new RuntimeException("Interface Names Cannot Be Empty");
        }

        if (!isset($node->implements)) {
            return;
        }

        foreach ($node->implements as $implement) {
            $implementedInterface = $implement->toString();
            $this->checkInterfaceMatch($implementedInterface, $className);
        }
    }

    private function checkInterfaceMatch(string $implementedInterface, string $className): void
    {
        foreach ($this->interfaceNames as $targetInterface) {
            if ($this->interfaceMatches($implementedInterface, $targetInterface)) {
                $this->implementingClasses[$targetInterface][] = $className;
            }
        }
    }

    private function interfaceMatches(string $implementedInterface, string $targetInterface): bool
    {
        return $implementedInterface === $targetInterface
            || str_ends_with($implementedInterface, "\\{$targetInterface}");
    }
}