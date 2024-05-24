<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\NodeVisitor;

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Node\AutoEscapeNode;
use OTGS\Toolset\Twig\Node\BlockNode;
use OTGS\Toolset\Twig\Node\BlockReferenceNode;
use OTGS\Toolset\Twig\Node\DoNode;
use OTGS\Toolset\Twig\Node\Expression\ConditionalExpression;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Expression\FilterExpression;
use OTGS\Toolset\Twig\Node\Expression\InlinePrint;
use OTGS\Toolset\Twig\Node\ImportNode;
use OTGS\Toolset\Twig\Node\ModuleNode;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\Node\PrintNode;
use OTGS\Toolset\Twig\NodeTraverser;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class EscaperNodeVisitor extends \OTGS\Toolset\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $statusStack = [];
    protected $blocks = [];
    protected $safeAnalysis;
    protected $traverser;
    protected $defaultStrategy = \false;
    protected $safeVars = [];
    public function __construct()
    {
        $this->safeAnalysis = new \OTGS\Toolset\Twig\NodeVisitor\SafeAnalysisNodeVisitor();
    }
    protected function doEnterNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\ModuleNode) {
            if ($env->hasExtension('OTGS\\Toolset\\Twig\\Extension\\EscaperExtension') && ($defaultStrategy = $env->getExtension('OTGS\\Toolset\\Twig\\Extension\\EscaperExtension')->getDefaultStrategy($node->getTemplateName()))) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\BlockNode) {
            $this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($env);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getAttribute('name');
        }
        return $node;
    }
    protected function doLeaveNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\ModuleNode) {
            $this->defaultStrategy = \false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FilterExpression) {
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\PrintNode && \false !== ($type = $this->needEscaping($env))) {
            $expression = $node->getNode('expr');
            if ($expression instanceof \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expression, $env, $type)) {
                return new \OTGS\Toolset\Twig\Node\DoNode($this->unwrapConditional($expression, $env, $type), $expression->getTemplateLine());
            }
            return $this->escapePrintNode($node, $env, $type);
        }
        if ($node instanceof \OTGS\Toolset\Twig\Node\AutoEscapeNode || $node instanceof \OTGS\Toolset\Twig\Node\BlockNode) {
            \array_pop($this->statusStack);
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping($env);
        }
        return $node;
    }
    private function shouldUnwrapConditional(\OTGS\Toolset\Twig\Node\Expression\ConditionalExpression $expression, \OTGS\Toolset\Twig\Environment $env, $type)
    {
        $expr2Safe = $this->isSafeFor($type, $expression->getNode('expr2'), $env);
        $expr3Safe = $this->isSafeFor($type, $expression->getNode('expr3'), $env);
        return $expr2Safe !== $expr3Safe;
    }
    private function unwrapConditional(\OTGS\Toolset\Twig\Node\Expression\ConditionalExpression $expression, \OTGS\Toolset\Twig\Environment $env, $type)
    {
        // convert "echo a ? b : c" to "a ? echo b : echo c" recursively
        $expr2 = $expression->getNode('expr2');
        if ($expr2 instanceof \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr2, $env, $type)) {
            $expr2 = $this->unwrapConditional($expr2, $env, $type);
        } else {
            $expr2 = $this->escapeInlinePrintNode(new \OTGS\Toolset\Twig\Node\Expression\InlinePrint($expr2, $expr2->getTemplateLine()), $env, $type);
        }
        $expr3 = $expression->getNode('expr3');
        if ($expr3 instanceof \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression && $this->shouldUnwrapConditional($expr3, $env, $type)) {
            $expr3 = $this->unwrapConditional($expr3, $env, $type);
        } else {
            $expr3 = $this->escapeInlinePrintNode(new \OTGS\Toolset\Twig\Node\Expression\InlinePrint($expr3, $expr3->getTemplateLine()), $env, $type);
        }
        return new \OTGS\Toolset\Twig\Node\Expression\ConditionalExpression($expression->getNode('expr1'), $expr2, $expr3, $expression->getTemplateLine());
    }
    private function escapeInlinePrintNode(\OTGS\Toolset\Twig\Node\Expression\InlinePrint $node, \OTGS\Toolset\Twig\Environment $env, $type)
    {
        $expression = $node->getNode('node');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        return new \OTGS\Toolset\Twig\Node\Expression\InlinePrint($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function escapePrintNode(\OTGS\Toolset\Twig\Node\PrintNode $node, \OTGS\Toolset\Twig\Environment $env, $type)
    {
        if (\false === $type) {
            return $node;
        }
        $expression = $node->getNode('expr');
        if ($this->isSafeFor($type, $expression, $env)) {
            return $node;
        }
        $class = \get_class($node);
        return new $class($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
    }
    protected function preEscapeFilterNode(\OTGS\Toolset\Twig\Node\Expression\FilterExpression $filter, \OTGS\Toolset\Twig\Environment $env)
    {
        $name = $filter->getNode('filter')->getAttribute('value');
        $type = $env->getFilter($name)->getPreEscape();
        if (null === $type) {
            return $filter;
        }
        $node = $filter->getNode('node');
        if ($this->isSafeFor($type, $node, $env)) {
            return $filter;
        }
        $filter->setNode('node', $this->getEscaperFilter($type, $node));
        return $filter;
    }
    protected function isSafeFor($type, \OTGS\Toolset\Twig_NodeInterface $expression, $env)
    {
        $safe = $this->safeAnalysis->getSafe($expression);
        if (null === $safe) {
            if (null === $this->traverser) {
                $this->traverser = new \OTGS\Toolset\Twig\NodeTraverser($env, [$this->safeAnalysis]);
            }
            $this->safeAnalysis->setSafeVars($this->safeVars);
            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }
        return \in_array($type, $safe) || \in_array('all', $safe);
    }
    protected function needEscaping(\OTGS\Toolset\Twig\Environment $env)
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }
        return $this->defaultStrategy ? $this->defaultStrategy : \false;
    }
    protected function getEscaperFilter($type, \OTGS\Toolset\Twig_NodeInterface $node)
    {
        $line = $node->getTemplateLine();
        $name = new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression('escape', $line);
        $args = new \OTGS\Toolset\Twig\Node\Node([new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression((string) $type, $line), new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression(null, $line), new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression(\true, $line)]);
        return new \OTGS\Toolset\Twig\Node\Expression\FilterExpression($node, $name, $args, $line);
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('OTGS\\Toolset\\Twig\\NodeVisitor\\EscaperNodeVisitor', 'OTGS\\Toolset\\Twig_NodeVisitor_Escaper');
