<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Expression\FilterExpression;
/**
 * Adds a check for the __toString() method when the variable is an object and the sandbox is activated.
 *
 * When there is a simple Print statement, like {{ article }},
 * and if the sandbox is enabled, we need to check that the __toString()
 * method is allowed if 'article' is an object.
 *
 * Not used anymore, to be deprecated in 2.x and removed in 3.0
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxedPrintNode extends \OTGS\Toolset\Twig\Node\PrintNode
{
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ');
        $expr = $this->getNode('expr');
        if ($expr instanceof \OTGS\Toolset\Twig\Node\Expression\ConstantExpression) {
            $compiler->subcompile($expr)->raw(";\n");
        } else {
            $compiler->write('$this->env->getExtension(\'\\OTGS\\Toolset\\Twig\\Extension\\SandboxExtension\')->ensureToStringAllowed(')->subcompile($expr)->raw(");\n");
        }
    }
    /**
     * Removes node filters.
     *
     * This is mostly needed when another visitor adds filters (like the escaper one).
     *
     * @return Node
     */
    protected function removeNodeFilter(\OTGS\Toolset\Twig\Node\Node $node)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FilterExpression) {
            return $this->removeNodeFilter($node->getNode('node'));
        }
        return $node;
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\SandboxedPrintNode', 'OTGS\\Toolset\\Twig_Node_SandboxedPrint');
