<?php

/**
 * Apishka templater exception loader
 *
 * @uses Apishka_Templater_Exception
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Exception_Loader extends Apishka_Templater_Exception
{
    /**
     * Construct
     *
     * @param strgin    $message
     * @param int       $lineno
     * @param string    $filename
     * @param Exception $previous
     */

    public function __construct($message, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct($message, false, false, $previous);
    }
}
