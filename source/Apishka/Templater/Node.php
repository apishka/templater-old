<?php

/**
 * Apishka templater node
 *
 * @uses Apishka_Templater_NodeAbstract
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Node extends Apishka_Templater_NodeAbstract
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames()
    {
        return array(
            'base',
        );
    }
}
