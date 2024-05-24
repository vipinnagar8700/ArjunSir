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
use OTGS\Toolset\Twig\Node\Expression\Binary\AndBinary;
use OTGS\Toolset\Twig\Node\Expression\Test\DefinedTest;
use OTGS\Toolset\Twig\Node\Expression\Test\NullTest;
use OTGS\Toolset\Twig\Node\Expression\Unary\NotUnary;
use OTGS\Toolset\Twig\Node\Node;
class NullCoalesceExpression extends \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $left, \OTGS\Toolset\Twig_NodeInterface $right, $lineno)
    {
        $test = new \OTGS\Toolset\Twig\Node\Expression\Test\DefinedTest(clone $left, 'defined', new \OTGS\Toolset\Twig\Node\Node(), $left->getTemplateLine());
        // for "block()", we don't need the null test as the return value is always a string
        if (!$left instanceof \OTGS\Toolset\Twig\Node\Expression\BlockReferenceExpression) {
            $test = new \OTGS\Toolset\Twig\Node\Expression\Binary\AndBinary($test, new \OTGS\Toolset\Twig\Node\Expression\Unary\NotUnary(new \OTGS\Toolset\Twig\Node\Expression\Test\NullTest($left, 'null', new \OTGS\Toolset\Twig\Node\Node(), $left->getTemplateLine()), $left->getTemplateLine()), $left->getTemplateLine());
        }
        parent::__construct($test, $left, $right, $lineno);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        /*
         * This optimizes only one case. PHP 7 also supports more complex expressions
         * that can return null. So, for instance, if log is defined, log("foo") ?? "..." works,
         * but log($a["foo"]) ?? "..." does not if $a["foo"] is not defined. More advanced
         * cases might be implemented as an optimizer node visitor, but has not been done
         * as benefits are probably not worth the added complexity.
         */
        if (\PHP_VERSION_ID >= 70000 && $this->getNode('expr2') instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression) {
            $this->getNode('expr2')->setAttribute('always_defined', \true);
            $compiler->raw('((')->subcompile($this->getNode('expr2'))->raw(') ?? (')->subcompile($this->getNode('expr3'))->raw('))');
        } else {
            parent::compile($compiler);
        }
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\NullCoalesceExpression', 'OTGS\\Toolset\\Twig_Node_Expression_NullCoalesce');
