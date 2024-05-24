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
class MethodCallExpression extends \OTGS\Toolset\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\OTGS\Toolset\Twig\Node\Expression\AbstractExpression $node, $method, \OTGS\Toolset\Twig\Node\Expression\ArrayExpression $arguments, $lineno)
    {
        parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => \false], $lineno);
        if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression) {
            $node->setAttribute('always_defined', \true);
        }
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'))->raw('->')->raw($this->getAttribute('method'))->raw('(');
        $first = \true;
        foreach ($this->getNode('arguments')->getKeyValuePairs() as $pair) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $first = \false;
            $compiler->subcompile($pair['value']);
        }
        $compiler->raw(')');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\MethodCallExpression', 'OTGS\\Toolset\\Twig_Node_Expression_MethodCall');
