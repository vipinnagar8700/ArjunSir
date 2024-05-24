<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node\Expression;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\TwigFilter;
class FilterExpression extends \OTGS\Toolset\Twig\Node\Expression\CallExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $node, \OTGS\Toolset\Twig\Node\Expression\ConstantExpression $filterName, \OTGS\Toolset\Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        parent::__construct(['node' => $node, 'filter' => $filterName, 'arguments' => $arguments], [], $lineno, $tag);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $name = $this->getNode('filter')->getAttribute('value');
        $filter = $compiler->getEnvironment()->getFilter($name);
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'filter');
        $this->setAttribute('thing', $filter);
        $this->setAttribute('needs_environment', $filter->needsEnvironment());
        $this->setAttribute('needs_context', $filter->needsContext());
        $this->setAttribute('arguments', $filter->getArguments());
        if ($filter instanceof \OTGS\Toolset\Twig_FilterCallableInterface || $filter instanceof \OTGS\Toolset\Twig\TwigFilter) {
            $this->setAttribute('callable', $filter->getCallable());
        }
        if ($filter instanceof \OTGS\Toolset\Twig\TwigFilter) {
            $this->setAttribute('is_variadic', $filter->isVariadic());
        }
        $this->compileCallable($compiler);
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\FilterExpression', 'OTGS\\Toolset\\Twig_Node_Expression_Filter');
