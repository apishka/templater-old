<?php

/**
 * Apishka templater node expression abstract
 *
 * @uses Apishka_Templater_NodeAbstract
 * @abstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_Node_ExpressionAbstract extends Apishka_Templater_NodeAbstract
{
    /**
     * Get precedence
     *
     * @abstract
     * @return int
     */

    abstract public function getPrecedence();

    /**
     * Get associativity
     *
     * @abstract
     * @return int
     */

    abstract public function getAssociativity();
}
