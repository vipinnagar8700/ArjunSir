<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Node\Expression\Test;

use OTGS\Toolset\Twig\Compiler;
use OTGS\Toolset\Twig\Error\SyntaxError;
use OTGS\Toolset\Twig\Node\Expression\ArrayExpression;
use OTGS\Toolset\Twig\Node\Expression\BlockReferenceExpression;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Expression\FunctionExpression;
use OTGS\Toolset\Twig\Node\Expression\GetAttrExpression;
use OTGS\Toolset\Twig\Node\Expression\NameExpression;
use OTGS\Toolset\Twig\Node\Expression\TestExpression;
/**
 * Checks if a variable is defined in the current context.
 *
 *    {# defined works with variable names and variable attributes #}
 *    {% if foo is defined %}
 *        {# ... #}
 *    {% endif %}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DefinedTest extends \OTGS\Toolset\Twig\Node\Expression\TestExpression
{
    public function __construct(\OTGS\Toolset\Twig_NodeInterface $node, $name, \OTGS\Toolset\Twig_NodeInterface $arguments = null, $lineno)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\Expression\GetAttrExpression) {
            $node->setAttribute('is_defined_test', \true);
            $this->changeIgnoreStrictCheck($node);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\Expression\BlockReferenceExpression) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FunctionExpression && 'constant' === $node->getAttribute('name')) {
            $node->setAttribute('is_defined_test', \true);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\Expression\ConstantExpression || $node instanceof \OTGS\Toolset\Twig\Node\Expression\ArrayExpression) {
            $node = new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression(\true, $node->getTemplateLine());
        } else {
            throw new \OTGS\Toolset\Twig\Error\SyntaxError('The "defined" test only works with simple variables.', $lineno);
        }
        parent::__construct($node, $name, $arguments, $lineno);
    }
    protected function changeIgnoreStrictCheck(\OTGS\Toolset\Twig\Node\Expression\GetAttrExpression $node)
    {
        $node->setAttribute('ignore_strict_check', \true);
        if ($node->getNode('node') instanceof \OTGS\Toolset\Twig\Node\Expression\GetAttrExpression) {
            $this->changeIgnoreStrictCheck($node->getNode('node'));
        }
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->subcompile($this->getNode('node'));
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\Expression\\Test\\DefinedTest', 'OTGS\\Toolset\\Twig_Node_Expression_Test_Defined');
