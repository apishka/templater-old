<?php

/**
 * Apishka templater node expression binary
 *
 * @uses Apishka_Templater_Node_ExpressionAbstract
 * @abstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_Node_Expression_BinaryAbstract extends Apishka_Templater_Node_ExpressionAbstract
{
    /**
     * Get supported types
     *
     * @return array
     */

    public function getSupportedTypes()
    {
        return array(
            'binary' => 1,
        );
    }

    /**
     * Construct
     *
     * @param Apishka_Templater_Node $left
     * @param Apishka_Templater_Node $right
     * @param int                    $lineno
     */

    public function __construct(Apishka_Templater_Node $left, Apishka_Templater_Node $right, $lineno)
    {
        parent::__construct(
            array(
                'left'  => $left,
                'right' => $right,
            ),
            array(),
            $lineno
        );
    }

    /**
     * Compile
     *
     * @param Apishka_Templater_Compiler $compiler
     */

    public function compile(Apishka_Templater_Compiler $compiler)
    {
        $compiler
            ->raw('(')
            ->subcompile($this->getNode('left'))
            ->raw(' ')
        ;

        $this->operator($compiler);

        $compiler
            ->raw(' ')
            ->subcompile($this->getNode('right'))
            ->raw(')')
        ;
    }

    /**
     * Operator
     *
     * @param Apishka_Templater_Compiler $compiler
     * @abstract
     *
     * @return Apishka_Templater_Compiler
     */

    abstract public function operator(Apishka_Templater_Compiler $compiler);
}
