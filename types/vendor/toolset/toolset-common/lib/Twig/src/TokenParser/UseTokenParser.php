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

use OTGS\Toolset\Twig\Error\SyntaxError;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\Token;
/**
 * Imports blocks defined in another template into the current template.
 *
 *    {% extends "base.html" %}
 *
 *    {% use "blocks.html" %}
 *
 *    {% block title %}{% endblock %}
 *    {% block content %}{% endblock %}
 *
 * @see https://twig.symfony.com/doc/templates.html#horizontal-reuse for details.
 *
 * @final
 */
class UseTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $template = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        if (!$template instanceof \OTGS\Toolset\Twig\Node\Expression\ConstantExpression) {
            throw new \OTGS\Toolset\Twig\Error\SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        $targets = [];
        if ($stream->nextIf('with')) {
            do {
                $name = $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue();
                $alias = $name;
                if ($stream->nextIf('as')) {
                    $alias = $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue();
                }
                $targets[$name] = new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression($alias, -1);
                if (!$stream->nextIf(\OTGS\Toolset\Twig\Token::PUNCTUATION_TYPE, ',')) {
                    break;
                }
            } while (\true);
        }
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $this->parser->addTrait(new \OTGS\Toolset\Twig\Node\Node(['template' => $template, 'targets' => new \OTGS\Toolset\Twig\Node\Node($targets)]));
        return new \OTGS\Toolset\Twig\Node\Node();
    }
    public function getTag()
    {
        return 'use';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\UseTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Use');
