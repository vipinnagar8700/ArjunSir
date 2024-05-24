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
class PowerBinary extends \OTGS\Toolset\Twig\Node\Expression\Binary\AbstractBinary
{
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        if (\PHP_VERSION_ID >= 50600) {
            return parent::compile($compiler);
        }
        $compiler->raw('pow(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
    }
    public function operator(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        return $compiler->raw('**');
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Binary\\PowerBinary', 'OTGS\\Toolset\\Twig_Node_Expression_Binary_Power');
