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

use OTGS\Toolset\Twig\Node\BlockNode;
use OTGS\Toolset\Twig\Node\Expression\BlockReferenceExpression;
use OTGS\Toolset\Twig\Node\Expression\ConstantExpression;
use OTGS\Toolset\Twig\Node\PrintNode;
use OTGS\Toolset\Twig\Token;
/**
 * Filters a section of a template by applying filters.
 *
 *   {% filter upper %}
 *      This text becomes uppercase
 *   {% endfilter %}
 *
 * @final
 */
class FilterTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $name = $this->parser->getVarName();
        $ref = new \OTGS\Toolset\Twig\Node\Expression\BlockReferenceExpression(new \OTGS\Toolset\Twig\Node\Expression\ConstantExpression($name, $token->getLine()), null, $token->getLine(), $this->getTag());
        $filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $block = new \OTGS\Toolset\Twig\Node\BlockNode($name, $body, $token->getLine());
        $this->parser->setBlock($name, $block);
        return new \OTGS\Toolset\Twig\Node\PrintNode($filter, $token->getLine(), $this->getTag());
    }
    public function decideBlockEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endfilter');
    }
    public function getTag()
    {
        return 'filter';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\FilterTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Filter');
