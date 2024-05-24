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

use OTGS\Toolset\Twig\Node\Expression\AssignNameExpression;
use OTGS\Toolset\Twig\Node\ImportNode;
use OTGS\Toolset\Twig\Token;
/**
 * Imports macros.
 *
 *   {% import 'forms.html' as forms %}
 *
 * @final
 */
class ImportTokenParser extends \OTGS\Toolset\Twig\TokenParser\AbstractTokenParser
{
    public function parse(\OTGS\Toolset\Twig\Token $token)
    {
        $macro = $this->parser->getExpressionParser()->parseExpression();
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE, 'as');
        $var = new \OTGS\Toolset\Twig\Node\Expression\AssignNameExpression($this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::NAME_TYPE)->getValue(), $token->getLine());
        $this->parser->getStream()->expect(\OTGS\Toolset\Twig\Token::BLOCK_END_TYPE);
        $this->parser->addImportedSymbol('template', $var->getAttribute('name'));
        return new \OTGS\Toolset\Twig\Node\ImportNode($macro, $var, $token->getLine(), $this->getTag());
    }
    public function getTag()
    {
        return 'import';
    }
}
\class_alias('OTGS\\Toolset\\Twig\\TokenParser\\ImportTokenParser', 'OTGS\\Toolset\\Twig_TokenParser_Import');
