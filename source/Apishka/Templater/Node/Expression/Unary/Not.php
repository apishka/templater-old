<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Apishka_Templater_Node_Expression_Unary_Not extends Apishka_Templater_Node_Expression_UnaryAbstract
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames()
    {
        return array(
            'not',
        );
    }

    /**
     * Operator
     *
     * @param Apishka_Templater_Compiler $compiler
     */

    public function operator(Apishka_Templater_Compiler $compiler)
    {
        $compiler->raw('!');
    }

    /**
     * Get precedence
     *
     * @return int
     */

    public function getPrecedence()
    {
        return 500;
    }

    /**
     * Get associativity
     *
     * @return int
     */

    public function getAssociativity()
    {
        return null;
    }
}
