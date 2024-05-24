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

use OTGS\Toolset\Twig\Node\WithNode;
use OTGS\Toolset\Twig\Token;
/**
 * Creates a nested scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final
 */
class WithTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $stream = $this->parser->getStream();
        $variables = null;
        $only = \false;
        if (!$stream->test(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE)) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
            $only = $stream->nextIf(\OTGS\Toolset\Twig\Token::NAME_TYPE, 'only');
        }
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideWithEnd'], \true);
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        return new \OTGS\Toolset\Twig\Node\WithNode($body, $variables, $only, $token->getLine(), $this->getTag());
    }
    public function decideWithEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endwith');
    }
    public function getTag()
    {
        return 'with';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\WithTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_With');
