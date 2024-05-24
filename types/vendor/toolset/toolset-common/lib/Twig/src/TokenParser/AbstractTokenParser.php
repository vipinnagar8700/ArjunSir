<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OTGS\Toolset\Twig\TokenParser;

use OTGS\Toolset\Twig\Parser;
/**
 * Base class for all token parsers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AbstractTokenParser implements \OTGS\Toolset\Twig\TokenParser\TokenParserInterface
{
    /**
     * @var Parser
     */
    protected $parser;
    public function setParser(\OTGS\Toolset\Twig\Parser $parser)
    {
        $this->parser = $parser;
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\AbstractTokenParser', 'OTGS\\Toolset\\Twig_TokenParser');
