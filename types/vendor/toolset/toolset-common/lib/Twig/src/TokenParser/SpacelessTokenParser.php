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

use OTGS\Toolset\Twig\Node\SpacelessNode;
use OTGS\Toolset\Twig\Token;
/**
 * Remove whitespaces between HTML tags.
 *
 *   {% spaceless %}
 *      <div>
 *          <strong>foo</strong>
 *      </div>
 *   {% endspaceless %}
 *   {# output will be <div><strong>foo</strong></div> #}
 *
 * @final
 */
class SpacelessTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideSpacelessEnd'], \true);
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        return new \OTGS\Toolset\Twig\Node\SpacelessNode($body, $lineno, $this->getTag());
    }
    public function decideSpacelessEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endspaceless');
    }
    public function getTag()
    {
        return 'spaceless';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\SpacelessTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Spaceless');
