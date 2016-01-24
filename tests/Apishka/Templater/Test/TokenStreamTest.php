<?php

/**
 * Apishka templater test token stream test
 *
 * @uses PHPUnit_Framework_TestCase
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Test_TokenStreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tokens
     *
     * @var array
     */

    protected static $_tokens;

    /**
     * Set up
     */

    public function setUp()
    {
        self::$_tokens = array(
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 1, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 2, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 3, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 4, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 5, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 6, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_TEXT, 7, 1),
            new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_EOF, 0, 1),
        );
    }

    /**
     * Test next
     */

    public function testNext()
    {
        $stream = new Apishka_Templater_TokenStream(self::$_tokens);

        $repr = array();
        while (!$stream->isEOF())
        {
            $token = $stream->next();

            $repr[] = $token->getValue();
        }

        $this->assertEquals('1, 2, 3, 4, 5, 6, 7', implode(', ', $repr), '->next() advances the pointer and returns the current token');
    }

    /**
     * Test end of template next
     *
     * @expectedException Apishka_Templater_Exception_Syntax
     * @expectedMessage   Unexpected end of template
     */

    public function testEndOfTemplateNext()
    {
        $stream = new Apishka_Templater_TokenStream(
            array(
                new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_BLOCK_START, 1, 1),
            )
        );

        while (!$stream->isEOF())
        {
            $stream->next();
        }
    }

    /**
     * Test end of template look
     *
     * @expectedException Apishka_Templater_Exception_Syntax
     * @expectedMessage   Unexpected end of template
     */

    public function testEndOfTemplateLook()
    {
        $stream = new Apishka_Templater_TokenStream(
            array(
                new Apishka_Templater_Token(Apishka_Templater_Token::TYPE_BLOCK_START, 1, 1),
            )
        );

        while (!$stream->isEOF())
        {
            $stream->look();
            $stream->next();
        }
    }
}
