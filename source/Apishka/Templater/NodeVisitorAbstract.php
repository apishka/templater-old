<?php

/**
 * Apishka templater node visitor abstract
 *
 * @uses Apishka_Templater_NodeVisitorInterface
 * @abstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_NodeVisitorAbstract implements Apishka_Templater_NodeVisitorInterface
{
    /**
     * Enter node
     *
     * @param Apishka_Templater_NodeInterface $node
     * @final
     *
     * @return Apishka_Templater_NodeInterface
     */

    final public function enterNode(Apishka_Templater_NodeInterface $node)
    {
        if (!$node instanceof Apishka_Templater_Node)
            throw new LogicException('Apishka_Templater_NodeVisitorAbstract only supports Apishka_Templater_Node instances.');

        return $this->doEnterNode($node);
    }

    /**
     * Leave node
     *
     * @param Apishka_Templater_NodeInterface $node
     * @final
     *
     * @return Apishka_Templater_NodeInterface
     */

    final public function leaveNode(Apishka_Templater_NodeInterface $node)
    {
        if (!$node instanceof Apishka_Templater_Node)
            throw new LogicException('Apishka_Templater_NodeVisitorAbstract only supports Apishka_Templater_Node instances.');

        return $this->doLeaveNode($node);
    }

    /**
     * Called before child nodes are visited.
     *
     * @param Apishka_Templater_Node $node The node to visit
     *
     * @return Apishka_Templater_Node The modified node
     */

    abstract protected function doEnterNode(Apishka_Templater_Node $node);

    /**
     * Called after child nodes are visited.
     *
     * @param Apishka_Templater_Node $node The node to visit
     *
     * @return Apishka_Templater_Node|false The modified node or false if the node must be removed
     */

    abstract protected function doLeaveNode(Apishka_Templater_Node $node);
}
