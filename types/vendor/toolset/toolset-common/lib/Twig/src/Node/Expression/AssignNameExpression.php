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
class AssignNameExpression extends \OTGS\Toolset\Twig\Node\Expression\NameExpression
{
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->raw('$context[')->string($this->getAttribute('name'))->raw(']');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\AssignNameExpression', 'OTGS\\Toolset\\Twig_Node_Expression_AssignName');
