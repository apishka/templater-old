<?php

/**
 * Apishka templater config
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Manager
{
    /**
     * Instance
     *
     * @static
     *
     * @var mixed
     */

    private static $_instance = null;

    /**
     * Binary operators
     *
     * @var array
     */

    private $_binary_operators = null;

    /**
     * Unary operators
     *
     * @var array
     */

    private $_unary_operators = null;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @static
     */

    public static function getInstance()
    {
        if (self::$_instance === null)
            self::$_instance = new static();

        return self::$_instance;
    }

    /**
     * Clear instance
     *
     * @static
     */

    public static function clearInstance()
    {
        self::$_instance = null;
    }


    /**
     * Get unary operators
     *
     * @return array
     */

    public function getUnaryOperators()
    {
        if ($this->_unary_operators === null)
        {
            $this->_unary_operators = Apishka_Templater_Node_Expression_UnaryRouter::apishka()->getData();
        }

        return $this->_unary_operators;
    }

    /**
     * Get binary operators
     *
     * @return array
     */

    public function getBinaryOperators()
    {
        if ($this->_binary_operators === null)
        {
            $this->_binary_operators = Apishka_Templater_Node_Expression_BinaryRouter::apishka()->getData();
        }

        return $this->_binary_operators;
    }

    /**
     * Construct
     */

    protected function __construct()
    {
    }

    /**
     * Clone
     */

    private function __clone()
    {
    }

    /**
     * Wakeup
     */

    private function __wakeup()
    {
    }
}
