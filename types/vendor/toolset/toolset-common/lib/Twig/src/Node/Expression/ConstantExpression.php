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
class ConstantExpression extends \OTGS\Toolset\Twig\Node\Expression\AbstractExpression
{
    public function __construct($value, $lineno)
    {
        parent::__construct([], ['value' => $value], $lineno);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->repr($this->getAttribute('value'));
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\ConstantExpression', 'OTGS\\Toolset\\Twig_Node_Expression_Constant');
