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

use OTGS\Toolset\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use OTGS\Toolset\Twig\Profiler\Profile;
class ProfilerExtension extends \OTGS\Toolset\Twig\Extension\AbstractExtension
{
    private $actives = [];
    public function __construct(\OTGS\Toolset\Twig\Profiler\Profile $profile)
    {
        $this->actives[] = $profile;
    }
    public function enter(\OTGS\Toolset\Twig\Profiler\Profile $profile)
    {
        $this->actives[0]->addProfile($profile);
        \array_unshift($this->actives, $profile);
    }
    public function leave(\OTGS\Toolset\Twig\Profiler\Profile $profile)
    {
        $profile->leave();
        \array_shift($this->actives);
        if (1 === \count($this->actives)) {
            $this->actives[0]->leave();
        }
    }
    public function getNodeVisitors()
    {
        return [new \OTGS\Toolset\Twig\Profiler\NodeVisitor\ProfilerNodeVisitor(\get_class($this))];
    }
    public function getName()
    {
        return 'profiler';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Extension\\ProfilerExtension', 'OTGS\\Toolset\\Twig_Extension_Profiler');
