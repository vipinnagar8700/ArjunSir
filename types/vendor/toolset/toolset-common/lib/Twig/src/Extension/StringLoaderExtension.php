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

use OTGS\Toolset\Twig\TwigFunction;
/**
 * @final
 */
class StringLoaderExtension extends \OTGS\Toolset\Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return [new \OTGS\Toolset\Twig\TwigFunction('template_from_string', 'twig_template_from_string', ['needs_environment' => \true])];
    }
    public function getName()
    {
        return 'string_loader';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\Extension\\StringLoaderExtension', 'OTGS\\Toolset\\Twig_Extension_StringLoader');
namespace OTGS\Toolset;

use OTGS\Toolset\Twig\Environment;
use OTGS\Toolset\Twig\TemplateWrapper;
/**
 * Loads a template from a string.
 *
 *     {{ include(template_from_string("Hello {{ name }}")) }}
 *
 * @param string $template A template as a string or object implementing __toString()
 * @param string $name     An optional name of the template to be used in error messages
 *
 * @return TemplateWrapper
 */
function twig_template_from_string(\OTGS\Toolset\Twig\Environment $env, $template, $name = null)
{
    return $env->createTemplate((string) $template, $name);
}
