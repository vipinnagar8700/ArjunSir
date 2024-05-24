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
 * Represents an include node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IncludeNode extends \OTGS\Toolset\Twig\Node\Node implements \OTGS\Toolset\Twig\Node\NodeOutputInterface
{
    public function __construct(\OTGS\Toolset\Twig\Node\Expression\AbstractExpression $expr, \OTGS\Toolset\Twig\Node\Expression\AbstractExpression $variables = null, $only = \false, $ignoreMissing = \false, $lineno, $tag = null)
    {
        $nodes = ['expr' => $expr];
        if (null !== $variables) {
            $nodes['variables'] = $variables;
        }
        parent::__construct($nodes, ['only' => (bool) $only, 'ignore_missing' => (bool) $ignoreMissing], $lineno, $tag);
    }
    public function compile(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->addDebugInfo($this);
        if ($this->getAttribute('ignore_missing')) {
            $template = $compiler->getVarName();
            $compiler->write(\sprintf("\$%s = null;\n", $template))->write("try {\n")->indent()->write(\sprintf('$%s = ', $template));
            $this->addGetTemplate($compiler);
            $compiler->raw(";\n")->outdent()->write("} catch (LoaderError \$e) {\n")->indent()->write("// ignore missing template\n")->outdent()->write("}\n")->write(\sprintf("if (\$%s) {\n", $template))->indent()->write(\sprintf('$%s->display(', $template));
            $this->addTemplateArguments($compiler);
            $compiler->raw(");\n")->outdent()->write("}\n");
        } else {
            $this->addGetTemplate($compiler);
            $compiler->raw('->display(');
            $this->addTemplateArguments($compiler);
            $compiler->raw(");\n");
        }
    }
    protected function addGetTemplate(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        $compiler->write('$this->loadTemplate(')->subcompile($this->getNode('expr'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(')');
    }
    protected function addTemplateArguments(\OTGS\Toolset\Twig\Compiler $compiler)
    {
        if (!$this->hasNode('variables')) {
            $compiler->raw(\false === $this->getAttribute('only') ? '$context' : '[]');
        } elseif (\false === $this->getAttribute('only')) {
            $compiler->raw('twig_array_merge($context, ')->subcompile($this->getNode('variables'))->raw(')');
        } else {
            $compiler->raw('twig_to_array(');
            $compiler->subcompile($this->getNode('variables'));
            $compiler->raw(')');
        }
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Node\\IncludeNode', 'OTGS\\Toolset\\Twig_Node_Include');
