<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Extension;

use OTGS\Toolset\Twig\NodeVisitor\OptimizerNodeVisitor;
/**
 * @final
 */
class OptimizerExtension extends \OTGS\Toolset\Twig\Extension\AbstractExtension
{
    protected $optimizers;
    public function __construct($optimizers = -1)
    {
        $this->optimizers = $optimizers;
    }
    public function getNodeVisitors()
    {
        return [new \OTGS\Toolset\Twig\NodeVisitor\OptimizerNodeVisitor($this->optimizers)];
    }
    public function getName()
    {
        return 'optimizer';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Extension\\OptimizerExtension', 'OTGS\\Toolset\\Twig_Extension_Optimizer');
