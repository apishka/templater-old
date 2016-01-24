<?php

/**
 * Apishka templater node traverser
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_NodeTraverser
{
    /**
     * Visitors
     *
     * @var array
     */

    protected $_visitors = array();

    /**
     * Construct
     *
     * @param array $visitors
     */

    public function __construct(array $visitors = array())
    {
        foreach ($visitors as $visitor)
            $this->addVisitor($visitor);
    }

    /**
     * Adds a visitor.
     *
     * @param Apishka_Templater_NodeVisitorInterface $visitor
     */

    public function addVisitor(Apishka_Templater_NodeVisitorInterface $visitor)
    {
        if (!isset($this->_visitors[$visitor->getPriority()]))
            $this->_visitors[$visitor->getPriority()] = array();

        $this->_visitors[$visitor->getPriority()][] = $visitor;
    }

    /**
     * Traverse
     *
     * @param Apishka_Templater_NodeInterface $node
     *
     * @return Apishka_Templater_NodeInterface
     */

    public function traverse(Apishka_Templater_NodeInterface $node)
    {
        ksort($this->_visitors);
        foreach ($this->_visitors as $visitors)
        {
            foreach ($visitors as $visitor)
                $node = $this->traverseForVisitor($visitor, $node);
        }

        return $node;
    }

    /**
     * Traverse for visitor
     *
     * @param Apishka_Templater_NodeVisitorInterface $visitor
     * @param Apishka_Templater_NodeInterface        $node
     *
     * @return Apishka_Templater_NodeVisitorInterface
     */

    protected function traverseForVisitor(Apishka_Templater_NodeVisitorInterface $visitor, Apishka_Templater_NodeInterface $node = null)
    {
        if ($node === null)
            return;

        $node = $visitor->enterNode($node);

        foreach ($node as $k => $n)
        {
            if (false !== $n = $this->traverseForVisitor($visitor, $n))
            {
                $node->setNode($k, $n);
            }
            else
            {
                $node->removeNode($k);
            }
        }

        return $visitor->leaveNode($node);
    }
}
