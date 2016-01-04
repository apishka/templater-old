<?php

/**
 * Apishka templater token parser abstract
 *
 * @uses Apishka_Templater_TokenParserInterface
 * @abstract
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

abstract class Apishka_Templater_TokenParserAbstract implements Apishka_Templater_TokenParserInterface
{
    /**
     * @var Apishka_Templater_Parser
     */

    protected $parser;

    /**
     * Sets the parser associated with this token parser.
     *
     * @param Apishka_Templater_Parser $parser
     *
     * @return Apishka_Templater_TokenParserAbstract
     */

    public function setParser(Apishka_Templater_Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }
}
