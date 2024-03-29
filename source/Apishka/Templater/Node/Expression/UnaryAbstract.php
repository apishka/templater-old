<?php

/**
 * Apishka templater node expression unary abstract
 *
 * @uses Apishka_Templater_Node_ExpressionAbstract
 * @abstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_Node_Expression_UnaryAbstract extends Apishka_Templater_Node_ExpressionAbstract
{
    /**
     * Get supported types
     *
     * @return array
     */

    public function getSupportedTypes()
    {
        return array(
            'unary' => 1,
        );
    }

    /**
     * Construct
     *
     * @param Apishka_Templater_Node $node
     * @param int                    $lineno
     */

    public function __construct(Apishka_Templater_Node $node, $lineno)
    {
        parent::__construct(
            array(
                'node' => $node,
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
        $compiler->raw(' ');
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
    }

    /**
     * Get precedence
     *
     * @abstract
     *
     * @return int
     */

    abstract public function getPrecedence();

    /**
     * Operator
     *
     * @param Apishka_Templater_Compiler $compiler
     * @abstract
     */

    abstract public function operator(Apishka_Templater_Compiler $compiler);
}
