<?php

/**
 * Apishka templater node interface
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

interface Apishka_Templater_NodeInterface
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames();

    /**
     * Get supported type
     *
     * @return array
     */

    public function getSupportedTypes();
}
