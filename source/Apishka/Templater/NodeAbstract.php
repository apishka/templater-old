<?php

/**
 * Apishka templater node
 *
 * @uses Apishka_Templater_NodeInterface
 * @uses Countable
 * @uses IteratorAggregate
 * @abstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_NodeAbstract implements Apishka_Templater_NodeInterface, Countable, IteratorAggregate
{
    /**
     * Nodes
     *
     * @var array
     */

    protected $_nodes;

    /**
     * Attributes
     *
     * @var array
     */

    protected $_attributes;

    /**
     * Line no
     *
     * @var int
     */

    protected $_lineno;

    /**
     * Tag
     *
     * @var string
     */

    protected $_tag;

    /**
     * Get supported type
     *
     * @return array
     */

    public function getSupportedTypes()
    {
        return array(
            'common' => 1,
        );
    }

    /**
     * Constructor.
     *
     * The nodes are automatically made available as properties ($this->node).
     * The attributes are automatically made available as array items ($this['name']).
     *
     * @param array  $nodes      An array of named nodes
     * @param array  $attributes An array of attributes (should not be nodes)
     * @param int    $lineno     The line number
     * @param string $tag        The tag name associated with the Node
     */

    public function __construct(array $nodes = array(), array $attributes = array(), $lineno = 0, $tag = null)
    {
        $this->_nodes       = $nodes;
        $this->_attributes  = $attributes;
        $this->_lineno      = $lineno;
        $this->_tag         = $tag;
    }

    /**
     * To string
     *
     * @return string
     */

    public function __toString()
    {
        $attributes = array();
        foreach ($this->_attributes as $name => $value)
        {
            $attributes[] = sprintf('%s: %s', $name, str_replace("\n", '', var_export($value, true)));
        }

        $repr = array(get_class($this) . '(' . implode(', ', $attributes));

        if (count($this->_nodes))
        {
            foreach ($this->_nodes as $name => $node)
            {
                $len = strlen($name) + 4;
                $noderepr = array();
                foreach (explode("\n", (string) $node) as $line)
                {
                    $noderepr[] = str_repeat(' ', $len) . $line;
                }

                $repr[] = sprintf('  %s: %s', $name, ltrim(implode("\n", $noderepr)));
            }

            $repr[] = ')';
        }
        else
        {
            $repr[0] .= ')';
        }

        return implode("\n", $repr);
    }

    /**
     * Compile
     *
     * @param Apishka_Templater_Compiler $compiler
     */

    public function compile(Apishka_Templater_Compiler $compiler)
    {
        foreach ($this->_nodes as $node)
        {
            $node->compile($compiler);
        }
    }

    /**
     * Get line
     *
     * @return int
     */

    public function getLine()
    {
        return $this->_lineno;
    }

    /**
     * Get node tag
     *
     * @return string
     */

    public function getNodeTag()
    {
        return $this->_tag;
    }

    /**
     * Returns true if the attribute is defined.
     *
     * @param string $name The attribute name
     *
     * @return bool true if the attribute is defined, false otherwise
     */

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->_attributes);
    }

    /**
     * Gets an attribute value by name.
     *
     * @param string $name
     *
     * @return mixed
     */

    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->_attributes)) {
            throw new LogicException(sprintf('Attribute "%s" does not exist for Node "%s".', $name, get_class($this)));
        }

        return $this->_attributes[$name];
    }

    /**
     * Sets an attribute by name to a value.
     *
     * @param string $name
     * @param mixed  $value
     */

    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;
    }

    /**
     * Removes an attribute by name.
     *
     * @param string $name
     */

    public function removeAttribute($name)
    {
        unset($this->_attributes[$name]);
    }

    /**
     * Returns true if the node with the given name exists.
     *
     * @param string $name
     *
     * @return bool
     */

    public function hasNode($name)
    {
        return array_key_exists($name, $this->_nodes);
    }

    /**
     * Gets a node by name.
     *
     * @param string $name
     *
     * @return Apishka_Templater_Node
     */

    public function getNode($name)
    {
        if (!array_key_exists($name, $this->_nodes))
            throw new LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, get_class($this)));

        return $this->_nodes[$name];
    }

    /**
     * Sets a node.
     *
     * @param string                 $name
     * @param Apishka_Templater_Node $node
     */

    public function setNode($name, $node = null)
    {
        $this->_nodes[$name] = $node;
    }

    /**
     * Removes a node by name.
     *
     * @param string $name
     */

    public function removeNode($name)
    {
        unset($this->_nodes[$name]);
    }

    /**
     * Count
     *
     * @return int
     */

    public function count()
    {
        return count($this->_nodes);
    }

    /**
     * Get iterator
     *
     * @return ArrayIterator
     */

    public function getIterator()
    {
        return new ArrayIterator($this->_nodes);
    }
}
