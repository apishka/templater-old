<?php

/**
 * Apishka templater node expression unary neg
 *
 * @easy-extend-base
 *
 * @uses Apishka_Templater_Node_Expression_UnaryAbstract
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Node_Expression_Unary_Neg extends Apishka_Templater_Node_Expression_UnaryAbstract
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames()
    {
        return array(
            '-',
        );
    }

    /**
     * Operator
     *
     * @param Apishka_Templater_Compiler $compiler
     */

    public function operator(Apishka_Templater_Compiler $compiler)
    {
        $compiler->raw('-');
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
}
