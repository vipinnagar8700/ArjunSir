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
use OTGS\Toolset\Twig\Node\Node;
/**
 * Represents an arrow function.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ArrowFunctionExpression extends \OTGS\Toolset\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\OTGS\Toolset\Twig\Node\Expression\AbstractExpression $expr, \OTGS\Toolset\Twig\Node\Node $names, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr, 'names' => $names], [], $lineno, $tag);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->raw('function (');
        foreach ($this->getNode('names') as $i => $name) {
            if ($i) {
                $compiler->raw(', ');
            }
            $compiler->raw('$__')->raw($name->getAttribute('name'))->raw('__');
        }
        $compiler->raw(') use ($context) { ');
        foreach ($this->getNode('names') as $name) {
            $compiler->raw('$context["')->raw($name->getAttribute('name'))->raw('"] = $__')->raw($name->getAttribute('name'))->raw('__; ');
        }
        $compiler->raw('return ')->subcompile($this->getNode('expr'))->raw('; }');
    }
}
