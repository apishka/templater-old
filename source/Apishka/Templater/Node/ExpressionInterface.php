<?php

/**
 * Apishka templater node expression interface
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

interface Apishka_Templater_Node_ExpressionInterface
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
