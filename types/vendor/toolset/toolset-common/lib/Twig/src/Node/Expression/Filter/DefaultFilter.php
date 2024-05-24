<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node\Expression\Filter;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\Node\Expression\ConditionalExpression;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Expression\FilterExpression;
use OTGS\Toolset\Twig\Node\Expression\GetAttrExpression;
use OTGS\Toolset\Twig\Node\Expression\NameExpression;
use OTGS\Toolset\Twig\Node\Expression\Test\DefinedTest;
use OTGS\Toolset\Twig\Node\Node;
/**
 * Returns the value or the default value when it is undefined or empty.
 *
 *  {{ var.foo|default('foo item on var is not defined') }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefaultFilter extends \OTGS\Toolset\Twig\Node\Expression\FilterExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $node, \OTGS\Toolset\Twig\Node\Expression\ConstantExpression $filterName, \OTGS\Toolset\Twig_NodeInterface $arguments, $lineno, $tag = null)
    {
        $default = new \OTGS\Toolset\Twig\Node\Expression\FilterExpression($node, new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());
        if ('default' === $filterName->getAttribute('value') && ($node instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression || $node instanceof \OTGS\Toolset\Twig\Node\Expression\GetAttrExpression)) {
            $test = new \OTGS\Toolset\Twig\Node\Expression\Test\DefinedTest(clone $node, 'defined', new \OTGS\Toolset\Twig\Node\Node(), $node->getTemplateLine());
            $false = \count($arguments) ? $arguments->getNode(0) : new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression('', $node->getTemplateLine());
            $node = new \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression($test, $default, $false, $node->getTemplateLine());
        } else {
            $node = $default;
        }
        parent::__construct($node, $filterName, $arguments, $lineno, $tag);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Filter\\DefaultFilter', 'OTGS\\Toolset\\Twig_Node_Expression_Filter_Default');
