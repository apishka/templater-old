<?php

/**
 * Apishka templater token parser interface
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

interface Apishka_Templater_TokenParserInterface
{
    /**
     * Get supported names
     *
     * @return array
     */

    public function getSupportedNames();

    /**
     * Sets the parser associated with this token parser.
     *
     * @param Apishka_Templater_Parser $parser A Apishka_Templater_Parser instance
     *
     * @return Apishka_Templater_TokenParserInterface
     */

    public function setParser(Apishka_Templater_Parser $parser);

    /**
     * Parses a token and returns a node.
     *
     * @param Apishka_Templater_Token $token A Apishka_Templater_Token instance
     *
     * @throws Apishka_Templater_Exception_Syntax
     *
     * @return Apishka_Templater_Node A Apishka_Templater_Node instance
     */

    public function parse(Apishka_Templater_Token $token);

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */

    public function getTag();
}
