<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node\Expression\Binary;

use OTGS\Toolset\Twig\Compiler;
class InBinary extends \OTGS\Toolset\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->raw('twig_in_filter(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
    }
    public function operator(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        return $compiler->raw('in');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Binary\\InBinary', 'OTGS\\Toolset\\Twig_Node_Expression_Binary_In');
