<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node\Expression;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\TwigTest;
class TestExpression extends \OTGS\Toolset\Twig\Node\Expression\CallExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $node, $name, \OTGS\Toolset\Twig_NodeInterface $arguments = null, $lineno)
    {
        $nodes = ['node' => $node];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }
        parent::__construct($nodes, ['name' => $name], $lineno);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $test = $compiler->getEnvironment()->getTest($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'test');
        $this->setAttribute('thing', $test);
        if ($test instanceof \OTGS\Toolset\Twig\TwigTest) {
            $this->setAttribute('arguments', $test->getArguments());
        }
        if ($test instanceof \OTGS\Toolset\Twig_TestCallableInterface || $test instanceof \OTGS\Toolset\Twig\TwigTest) {
            $this->setAttribute('callable', $test->getCallable());
        }
        if ($test instanceof \OTGS\Toolset\Twig\TwigTest) {
            $this->setAttribute('is_variadic', $test->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\TestExpression', 'OTGS\\Toolset\\Twig_Node_Expression_Test');
