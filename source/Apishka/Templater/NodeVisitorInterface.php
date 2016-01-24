<?php

/**
 * Apishka templater node visitor interface
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

interface Apishka_Templater_NodeVisitorInterface
{
    /**
     * Called before child nodes are visited.
     *
     * @param Apishka_Templater_NodeInterface $node The node to visit
     *
     * @return Apishka_Templater_NodeInterface The modified node
     */

    public function enterNode(Apishka_Templater_NodeInterface $node);

    /**
     * Called after child nodes are visited.
     *
     * @param Apishka_Templater_NodeInterface $node The node to visit
     *
     * @return Apishka_Templater_NodeInterface|false The modified node or false if the node must be removed
     */

    public function leaveNode(Apishka_Templater_NodeInterface $node);

    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return int The priority level
     */

    public function getPriority();
}
