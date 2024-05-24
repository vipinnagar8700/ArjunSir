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
/**
 * Interface for node visitor classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface NodeVisitorInterface
{
    /**
     * Called before child nodes are visited.
     *
     * @return \Twig_NodeInterface The modified node
     */
    public function enterNode(\OTGS\Toolset\Twig_NodeInterface $node, \OTGS\Toolset\Twig\Environment $env);
    /**
     * Called after child nodes are visited.
     *
     * @return \Twig_NodeInterface|false|null The modified node or null if the node must be removed
     */
    public function leaveNode(\OTGS\Toolset\Twig_NodeInterface $node, \OTGS\Toolset\Twig\Environment $env);
    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return int The priority level
     */
    public function getPriority();
}
\class_alias('OTGS\\Toolset\\Twig\\NodeVisitor\\NodeVisitorInterface', 'OTGS\\Toolset\\Twig_NodeVisitorInterface');
// Ensure that the aliased name is loaded to keep BC for classes implementing the typehint with the old aliased name.
\class_exists('OTGS\\Toolset\\Twig\\Environment');
