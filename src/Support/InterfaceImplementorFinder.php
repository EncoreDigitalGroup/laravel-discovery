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
    private string $interfaceName = "";

    public function setInterfaceName(string $interfaceName): void
    {
        $this->interfaceName = $interfaceName;
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

    private function nodeImplements(Node\Stmt\Class_ $node, string $className): void
    {
        if ($this->interfaceName == Str::empty()) {
            throw new RuntimeException("Interface Name Property Cannot Be Empty String");
        }

        if (isset($node->implements)) {
            foreach ($node->implements as $implement) {
                $interfaceName = $implement->toString();
                if ($interfaceName === $this->interfaceName || str_ends_with($interfaceName, "\\{$this->interfaceName}")) {
                    $this->implementingClasses[] = $className;
                }
            }
        }
    }
}