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
use OTGS\Toolset\Twig\TwigFunction;
class FunctionExpression extends \OTGS\Toolset\Twig\Node\Expression\CallExpression
{
    public function __construct($name, \OTGS\Toolset\Twig_NodeInterface $arguments, $lineno)
    {
        parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => \false], $lineno);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $name = $this->getAttribute('name');
        $function = $compiler->getEnvironment()->getFunction($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'function');
        $this->setAttribute('thing', $function);
        $this->setAttribute('needs_environment', $function->needsEnvironment());
        $this->setAttribute('needs_context', $function->needsContext());
        $this->setAttribute('arguments', $function->getArguments());
        if ($function instanceof \OTGS\Toolset\Twig_FunctionCallableInterface || $function instanceof \OTGS\Toolset\Twig\TwigFunction) {
            $callable = $function->getCallable();
            if ('constant' === $name && $this->getAttribute('is_defined_test')) {
                $callable = 'twig_constant_is_defined';
            }
            $this->setAttribute('callable', $callable);
        }
        if ($function instanceof \OTGS\Toolset\Twig\TwigFunction) {
            $this->setAttribute('is_variadic', $function->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\FunctionExpression', 'OTGS\\Toolset\\Twig_Node_Expression_Function');
