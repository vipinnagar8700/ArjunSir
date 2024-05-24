<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Profiler\NodeVisitor;

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\Node\BlockNode;
use OTGS\Toolset\Twig\Node\BodyNode;
use OTGS\Toolset\Twig\Node\MacroNode;
use OTGS\Toolset\Twig\Node\ModuleNode;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\NodeVisitor\AbstractNodeVisitor;
use OTGS\Toolset\Twig\Profiler\Node\EnterProfileNode;
use OTGS\Toolset\Twig\Profiler\Node\LeaveProfileNode;
use OTGS\Toolset\Twig\Profiler\Profile;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class ProfilerNodeVisitor extends \OTGS\Toolset\Twig\NodeVisitor\AbstractNodeVisitor
{
    private $extensionName;
    public function __construct($extensionName)
    {
        $this->extensionName = $extensionName;
    }
    protected function doEnterNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        return $node;
    }
    protected function doLeaveNode(\OTGS\Toolset\Twig\Node\Node $node, \OTGS\Toolset\Twig\Environment $env)
    {
        if ($node instanceof \OTGS\Toolset\Twig\Node\ModuleNode) {
            $varName = $this->getVarName();
            $node->setNode('display_start', new \OTGS\Toolset\Twig\Node\Node([new \OTGS\Toolset\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \OTGS\Toolset\Twig\Profiler\Profile::TEMPLATE, $node->getTemplateName(), $varName), $node->getNode('display_start')]));
            $node->setNode('display_end', new \OTGS\Toolset\Twig\Node\Node([new \OTGS\Toolset\Twig\Profiler\Node\LeaveProfileNode($varName), $node->getNode('display_end')]));
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\BlockNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \OTGS\Toolset\Twig\Node\BodyNode([new \OTGS\Toolset\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \OTGS\Toolset\Twig\Profiler\Profile::BLOCK, $node->getAttribute('name'), $varName), $node->getNode('body'), new \OTGS\Toolset\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        } elseif ($node instanceof \OTGS\Toolset\Twig\Node\MacroNode) {
            $varName = $this->getVarName();
            $node->setNode('body', new \OTGS\Toolset\Twig\Node\BodyNode([new \OTGS\Toolset\Twig\Profiler\Node\EnterProfileNode($this->extensionName, \OTGS\Toolset\Twig\Profiler\Profile::MACRO, $node->getAttribute('name'), $varName), $node->getNode('body'), new \OTGS\Toolset\Twig\Profiler\Node\LeaveProfileNode($varName)]));
        }
        return $node;
    }
    private function getVarName()
    {
        return \sprintf('__internal_%s', \hash('sha256', $this->extensionName));
    }
    public function getPriority()
    {
        return 0;
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Profiler\\NodeVisitor\\ProfilerNodeVisitor', 'OTGS\\Toolset\\Twig_Profiler_NodeVisitor_Profiler');
