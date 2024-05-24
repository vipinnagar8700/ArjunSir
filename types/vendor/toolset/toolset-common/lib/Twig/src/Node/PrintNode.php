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
namespace OTGS\Toolset\Twig\Node;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\Node\Expression\AbstractExpression;
/**
 * Represents a node that outputs an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class PrintNode extends \OTGS\Toolset\Twig\Node\Node implements \OTGS\Toolset\Twig\Node\NodeOutputInterface
{
    public function __construct(\OTGS\Toolset\Twig\Node\Expression\AbstractExpression $expr, $lineno, $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ')->subcompile($this->getNode('expr'))->raw(";\n");
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\PrintNode', 'OTGS\\Toolset\\Twig_Node_Print');
