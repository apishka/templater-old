<?php

/**
 * Apishka templater token stream
 *
 * @author Evgeny Reykh <evgeny@reykh.com>
 */

class Apishka_Templater_TokenStream
{
    /**
     * Tokens
     *
     * @var mixed
     */

    protected $_tokens;

    /**
     * Current
     *
     * @var mixed
     */

    protected $_current;

    /**
     * Filename
     *
     * @var mixed
     */

    protected $_filename;

    /**
     * Constructor.
     *
     * @param array  $tokens   An array of tokens
     * @param string $filename The name of the filename which tokens are associated with
     */

    public function __construct(array $tokens, $filename = null)
    {
        $this->_tokens   = $tokens;
        $this->_current  = 0;
        $this->_filename = $filename;
    }

    /**
     * Returns a string representation of the token stream.
     *
     * @return string
     */

    public function __toString()
    {
        return implode("\n", $this->_tokens);
    }

    /**
     * Inject tokens
     *
     * @param array $tokens
     */

    public function injectTokens(array $tokens)
    {
        $this->_tokens = array_merge(array_slice($this->_tokens, 0, $this->_current), $tokens, array_slice($this->_tokens, $this->_current));
    }

    /**
     * Sets the pointer to the next token and returns the old one.
     *
     * @return Apishka_Templater_Token
     */

    public function next()
    {
        if (!isset($this->_tokens[++$this->_current]))
        {
            throw new Apishka_Templater_Error_Syntax('Unexpected end of template', $this->_tokens[$this->_current - 1]->getLine(), $this->_filename);
        }

        return $this->_tokens[$this->_current - 1];
    }

    /**
     * Tests a token, sets the pointer to the next one and returns it or throws a syntax error.
     *
     * @return Apishka_Templater_Token|null The next token if the condition is true, null otherwise
     */

    public function nextIf($primary, $secondary = null)
    {
        if ($this->_tokens[$this->_current]->test($primary, $secondary))
        {
            return $this->next();
        }
    }

    /**
     * Tests a token and returns it or throws a syntax error.
     *
     * @return Apishka_Templater_Token
     */

    public function expect($type, $value = null, $message = null)
    {
        $token = $this->_tokens[$this->_current];
        if (!$token->test($type, $value))
        {
            $line = $token->getLine();
            throw new Apishka_Templater_Error_Syntax(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s)',
                $message ? $message . '. ' : '',
                Apishka_Templater_Token::typeToEnglish($token->getType()), $token->getValue(),
                Apishka_Templater_Token::typeToEnglish($type), $value ? sprintf(' with value "%s"', $value) : ''),
                $line,
                $this->_filename
            );
        }
        $this->next();

        return $token;
    }

    /**
     * Looks at the next token.
     *
     * @param int $number
     *
     * @return Apishka_Templater_Token
     */

    public function look($number = 1)
    {
        if (!isset($this->_tokens[$this->_current + $number]))
        {
            throw new Apishka_Templater_Error_Syntax('Unexpected end of template', $this->_tokens[$this->_current + $number - 1]->getLine(), $this->_filename);
        }

        return $this->_tokens[$this->_current + $number];
    }

    /**
     * Tests the current token
     *
     * @return bool
     */

    public function test($primary, $secondary = null)
    {
        return $this->_tokens[$this->_current]->test($primary, $secondary);
    }

    /**
     * Checks if end of stream was reached
     *
     * @return bool
     */

    public function isEOF()
    {
        return $this->_tokens[$this->_current]->getType() === Apishka_Templater_Token::TYPE_EOF;
    }

    /**
     * Gets the current token
     *
     * @return Apishka_Templater_Token
     */

    public function getCurrent()
    {
        return $this->_tokens[$this->_current];
    }

    /**
     * Gets the filename associated with this stream
     *
     * @return string
     */

    public function getFilename()
    {
        return $this->_filename;
    }
}
