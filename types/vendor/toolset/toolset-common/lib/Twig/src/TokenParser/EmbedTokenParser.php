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

use OTGS\Toolset\Twig\Node\EmbedNode;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\Expression\NameExpression;
use OTGS\Toolset\Twig\Token;
/**
 * Embeds a template.
 *
 * @final
 */
class EmbedTokenParser extends \OTGS\Toolset\Twig\TokenParser\IncludeTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        list($variables, $only, $ignoreMissing) = $this->parseArguments();
        $parentToken = $fakeParentToken = new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::STRING_TYPE, '__parent__', $token->getLine());
        if ($parent instanceof \OTGS\Toolset\Twig\Node\Expression\ConstantExpression) {
            $parentToken = new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
        } elseif ($parent instanceof \OTGS\Toolset\Twig\Node\Expression\NameExpression) {
            $parentToken = new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());
        }
        // inject a fake parent to make the parent() function work
        $stream->injectTokens([new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::BLOCK_START_TYPE, '', $token->getLine()), new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::NAME_TYPE, 'extends', $token->getLine()), $parentToken, new \OTGS\Toolset\Twig\Token(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE, '', $token->getLine())]);
        $module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], \true);
        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }
        $this->parser->embedTemplate($module);
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        return new \OTGS\Toolset\Twig\Node\EmbedNode($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endembed');
    }
    public function getTag()
    {
        return 'embed';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\EmbedTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Embed');
