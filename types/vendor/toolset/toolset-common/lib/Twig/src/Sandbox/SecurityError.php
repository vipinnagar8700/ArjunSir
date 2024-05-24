<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\Sandbox;

use OTGS\Toolset\Twig\Error\Error;
/**
 * Exception thrown when a security error occurs at runtime.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityError extends \OTGS\Toolset\Twig\Error\Error
{
}
\class_alias('OTGS\\Toolset\\Twig\\Sandbox\\SecurityError', 'OTGS\\Toolset\\Twig_Sandbox_SecurityError');
