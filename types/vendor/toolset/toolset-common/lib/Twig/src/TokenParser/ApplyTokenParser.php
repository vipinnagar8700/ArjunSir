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

use OTGS\Toolset\Twig\Node\Expression\TempNameExpression;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\Node\PrintNode;
use OTGS\Toolset\Twig\Node\SetNode;
use OTGS\Toolset\Twig\Token;
/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapplys %}
 */
final class ApplyTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $name = $this->parser->getVarName();
        $ref = new \OTGS\Toolset\Twig\Node\Expression\TempNameExpression($name, $lineno);
        $ref->setAttribute('always_defined', \true);
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideApplyEnd'], \true);
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        return new \OTGS\Toolset\Twig\Node\Node([new \OTGS\Toolset\Twig\Node\SetNode(\true, $ref, $body, $lineno, $this->getTag()), new \OTGS\Toolset\Twig\Node\PrintNode($filter, $lineno, $this->getTag())]);
    }
    public function decideApplyEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endapply');
    }
    public function getTag()
    {
        return 'apply';
    }
}
