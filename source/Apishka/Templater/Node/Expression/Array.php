<?php

/**
 * Apishka templater node expression array
 *
 * @uses Apishka_Templater_Node_ExpressionAbstract
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Node_Expression_Array extends Apishka_Templater_Node_ExpressionAbstract
{
    /**
     * Index
     *
     * @var mixed
     */

    private $_index;

    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames()
    {
        return array(
            'array',
        );
    }

    /**
     * Construct
     *
     * @param array $elements
     * @param int $lineno
     */

    public function __construct(array $elements, $lineno)
    {
        parent::__construct($elements, array(), $lineno);

        $this->_index = -1;
        foreach ($this->getKeyValuePairs() as $pair)
        {
            if ($pair['key'] instanceof Apishka_Templater_Node_Expression_Constant && ctype_digit((string) $pair['key']->getAttribute('value')) && $pair['key']->getAttribute('value') > $this->_index)
            {
                $this->_index = $pair['key']->getAttribute('value');
            }
        }
    }

    /**
     * Get key value pairs
     *
     * @return array
     */

    public function getKeyValuePairs()
    {
        $pairs = array();
        foreach (array_chunk($this->_nodes, 2) as $pair)
        {
            $pairs[] = array(
                'key'   => $pair[0],
                'value' => $pair[1],
            );
        }

        return $pairs;
    }

    /**
     * Has element
     *
     * @param Apishka_Templater_Node_ExpressionAbstract $key
     * @return bool
     */

    public function hasElement(Apishka_Templater_Node_ExpressionAbstract $key)
    {
        foreach ($this->getKeyValuePairs() as $pair)
        {
            // we compare the string representation of the keys
            // to avoid comparing the line numbers which are not relevant here.
            if ((string) $key == (string) $pair['key'])
                return true;
        }

        return false;
    }

    /**
     * Add element
     *
     * @param Apishka_Templater_Node_ExpressionAbstract $value
     * @param Apishka_Templater_Node_ExpressionAbstract $key
     * @return Apishka_Templater_Node_Expression_Array
     */

    public function addElement(Apishka_Templater_Node_ExpressionAbstract $value, Apishka_Templater_Node_ExpressionAbstract $key = null)
    {
        if ($key === null)
            $key = Apishka_Templater_NodeRouter::apishka()->getItem('common:constant');
            //$key = new Apishka_Templater_Node_Expression_Constant(++$this->_index, $value->getLine());

        array_push($this->_nodes, $key, $value);

        return $this;
    }

    /**
     * Compile
     *
     * @param Apishka_Templater_Compiler $compiler
     */

    public function compile(Apishka_Templater_Compiler $compiler)
    {
        $compiler->raw('array(');
        $first = true;
        foreach ($this->getKeyValuePairs() as $pair)
        {
            if (!$first)
                $compiler->raw(', ');

            $first = false;

            $compiler
                ->subcompile($pair['key'])
                ->raw(' => ')
                ->subcompile($pair['value'])
            ;
        }

        $compiler->raw(')');
    }
}
