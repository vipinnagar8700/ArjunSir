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
use OTGS\Toolset\Twig\Node\BodyNode;
use OTGS\Toolset\Twig\Node\MacroNode;
use OTGS\Toolset\Twig\Node\Node;
use OTGS\Toolset\Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @final
 */
class MacroTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments(\true, \true);
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        if ($token = $stream->nextIf(\OTGS\Toolset\Twig\Token::NAME_TYPE)) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new \OTGS\Toolset\Twig\Error\SyntaxError(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $this->parser->setMacro($name, new \OTGS\Toolset\Twig\Node\MacroNode($name, new \OTGS\Toolset\Twig\Node\BodyNode([$body]), $arguments, $lineno, $this->getTag()));
        return new \OTGS\Toolset\Twig\Node\Node();
    }
    public function decideBlockEnd(\OTGS\Toolset\Twig\Token $token)
    {
        return $token->test('endmacro');
    }
    public function getTag()
    {
        return 'macro';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\MacroTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Macro');
