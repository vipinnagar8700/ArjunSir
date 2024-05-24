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
namespace OTGS\Toolset\Twig\Node\Expression\Unary;

use OTGS\Toolset\Twig\Compiler;
class PosUnary extends \OTGS\Toolset\Twig\Node\Expression\Unary\AbstractUnary
{
    public function operator(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->raw('+');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Unary\\PosUnary', 'OTGS\\Toolset\\Twig_Node_Expression_Unary_Pos');
