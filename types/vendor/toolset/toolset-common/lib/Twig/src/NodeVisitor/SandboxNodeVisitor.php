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
use OTGS\Toolset\Twig\Node\CheckSecurityNode;
use OTGS\Toolset\Twig\Node\CheckToStringNode;
use OTGS\Toolset\Twig\Node\Expression\Binary\ConcatBinary;
use OTGS\Toolset\Twig\Node\Expression\Binary\RangeBinary;
use OTGS\Toolset\Twig\Node\Expression\FilterExpression;
use OTGS\Toolset\Twig\Node\Expression\FunctionExpression;
use OTGS\Toolset\Twig\Node\Expression\GetAttrExpression;
use OTGS\Toolset\Twig\Node\Expression\NameExpression;
use OTGS\Toolset\Twig\Node\ModuleNode;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\Node\PrintNode;
use OTGS\Toolset\Twig\Node\SetNode;
/**
 * @final
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SandboxNodeVisitor extends \OTGS\Toolset\Twig\NodeVisitor\AbstractNodeVisitor
{
    protected $inAModule = \false;
    protected $tags;
    protected $filters;
    protected $functions;
    private $needsToStringWrap = \false;
    protected function doEnterNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\ModuleNode) {
            $this->inAModule = \true;
            $this->tags = [];
            $this->filters = [];
            $this->functions = [];
            return $node;
        } elseif ($this->inAModule) {
            // look for tags
            if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()])) {
                $this->tags[$node->getNodeTag()] = $node;
            }
            // look for filters
            if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')])) {
                $this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
            }
            // look for functions
            if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FunctionExpression && !isset($this->functions[$node->getAttribute('name')])) {
                $this->functions[$node->getAttribute('name')] = $node;
            }
            // the .. operator is equivalent to the range() function
            if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\Binary\RangeBinary && !isset($this->functions['range'])) {
                $this->functions['range'] = $node;
            }
            if ($node instanceof \OTGS\Toolset\Twig\Node\PrintNode) {
                $this->needsToStringWrap = \true;
                $this->wrapNode($node, 'expr');
            }
            if ($node instanceof \OTGS\Toolset\Twig\Node\SetNode && !$node->getAttribute('capture')) {
                $this->needsToStringWrap = \true;
            }
            // wrap outer nodes that can implicitly call __toString()
            if ($this->needsToStringWrap) {
                if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\Binary\ConcatBinary) {
                    $this->wrapNode($node, 'left');
                    $this->wrapNode($node, 'right');
                }
                if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FilterExpression) {
                    $this->wrapNode($node, 'node');
                    $this->wrapArrayNode($node, 'arguments');
                }
                if ($node instanceof \OTGS\Toolset\Twig\Node\Expression\FunctionExpression) {
                    $this->wrapArrayNode($node, 'arguments');
                }
            }
        }
        return $node;
    }
    protected function doLeaveNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\ModuleNode) {
            $this->inAModule = \false;
            $node->getNode('constructor_end')->setNode('_security_check', new \OTGS\Toolset\Twig\Node\Node([new \OTGS\Toolset\Twig\Node\CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
        } elseif ($this->inAModule) {
            if ($node instanceof \OTGS\Toolset\Twig\Node\PrintNode || $node instanceof \OTGS\Toolset\Twig\Node\SetNode) {
                $this->needsToStringWrap = \false;
            }
        }
        return $node;
    }
    private function wrapNode(\OTGS\Toolset\Twig\Node\Node $node, $name)
    {
        $expr = $node->getNode($name);
        if ($expr instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression || $expr instanceof \OTGS\Toolset\Twig\Node\Expression\GetAttrExpression) {
            $node->setNode($name, new \OTGS\Toolset\Twig\Node\CheckToStringNode($expr));
        }
    }
    private function wrapArrayNode(\OTGS\Toolset\Twig\Node\Node $node, $name)
    {
        $args = $node->getNode($name);
        foreach ($args as $name => $_) {
            $this->wrapNode($args, $name);
        }
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('OTGS\\Toolset\\Twig\\NodeVisitor\\SandboxNodeVisitor', 'OTGS\\Toolset\\Twig_NodeVisitor_Sandbox');
