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
namespace OTGS\Toolset\Twig\Node\Expression\Binary;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\Node\Expression\AbstractExpression;
abstract class AbstractBinary extends \OTGS\Toolset\Twig\Node\Expression\AbstractExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $left, \OTGS\Toolset\Twig_NodeInterface $right, $lineno)
    {
        parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->raw('(')->subcompile($this->getNode('left'))->raw(' ');
        $this->operator($compiler);
        $compiler->raw(' ')->subcompile($this->getNode('right'))->raw(')');
    }
    public abstract function operator(\OTGS\Toolset\Twig\Compiler $compiler);
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Binary\\AbstractBinary', 'OTGS\\Toolset\\Twig_Node_Expression_Binary');
