<?php

/**
 * Apishka templater node expression binary add
 *
 * @uses Apishka_Templater_Node_Expression_BinaryAbstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Node_Expression_Binary_Add extends Apishka_Templater_Node_Expression_BinaryAbstract
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames()
    {
        return array(
            '+',
        );
    }

    /**
     * Operator
     *
     * @param Apishka_Templater_Compiler $compiler
     *
     * @return Apishka_Templater_Compiler
     */

    public function operator(Apishka_Templater_Compiler $compiler)
    {
        return $compiler->raw('+');
    }
}
