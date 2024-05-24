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
use OTGS\Toolset\Twig\Node\Expression\AssignNameExpression;
use OTGS\Toolset\Twig\Node\ImportNode;
use OTGS\Toolset\Twig\Token;
/**
 * Imports macros.
 *
 *   {% from 'forms.html' import forms %}
 *
 * @final
 */
class FromTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();
        $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE, 'import');
        $targets = [];
        do {
            $name = $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue();
            $alias = $name;
            if ($stream->nextIf('as')) {
                $alias = $stream->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue();
            }
            $targets[$name] = $alias;
            if (!$stream->nextIf(\OTGS\Toolset\Twig\Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        } while (\true);
        $stream->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $var = new \OTGS\Toolset\Twig\Node\Expression\AssignNameExpression($this->parser->getVarName(), $token->getLine());
        $node = new \OTGS\Toolset\Twig\Node\ImportNode($macro, $var, $token->getLine(), $this->getTag());
        foreach ($targets as $name => $alias) {
            if ($this->parser->isReservedMacroName($name)) {
                throw new \OTGS\Toolset\Twig\Error\SyntaxError(\sprintf('"%s" cannot be an imported macro as it is a reserved keyword.', $name), $token->getLine(), $stream->getSourceContext());
            }
            $this->parser->addImportedSymbol('function', $alias, 'get' . $name, $var);
        }
        return $node;
    }
    public function getTag()
    {
        return 'from';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\FromTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_From');
