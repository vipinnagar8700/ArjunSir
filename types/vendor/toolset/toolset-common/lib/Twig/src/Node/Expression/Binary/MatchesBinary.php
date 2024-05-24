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
class MatchesBinary extends \OTGS\Toolset\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->raw('preg_match(')->subcompile($this->getNode('right'))->raw(', ')->subcompile($this->getNode('left'))->raw(')');
    }
    public function operator(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        return $compiler->raw('');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Binary\\MatchesBinary', 'OTGS\\Toolset\\Twig_Node_Expression_Binary_Matches');
