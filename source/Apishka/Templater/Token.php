<?php

/**
 * Apishka templater token
 *
 * @author Evgeny Reykh <evgeny@reykh.com>
 */

class Apishka_Templater_Token
{
    /**
     * Value
     *
     * @var mixed
     */

    protected $_value;

    /**
     * Type
     *
     * @var mixed
     */

    protected $_type;

    /**
     * Lineno
     *
     * @var mixed
     */

    protected $_lineno;

    /**
     * Type constants
     */

    const TYPE_EOF                  = -1;
    const TYPE_TEXT                 = 0;
    const TYPE_BLOCK_START          = 1;
    const TYPE_VAR_START            = 2;
    const TYPE_BLOCK_END            = 3;
    const TYPE_VAR_END              = 4;
    const TYPE_NAME                 = 5;
    const TYPE_NUMBER               = 6;
    const TYPE_STRING               = 7;
    const TYPE_OPERATOR             = 8;
    const TYPE_PUNCTUATION          = 9;
    const TYPE_INTERPOLATION_START  = 10;
    const TYPE_INTERPOLATION_END    = 11;

    /**
     * Constructor.
     *
     * @param int    $type   The type of the token
     * @param string $value  The token value
     * @param int    $lineno The line position in the source
     */

    public function __construct($type, $value, $lineno)
    {
        $this->_type   = $type;
        $this->_value  = $value;
        $this->_lineno = $lineno;
    }

    /**
     * Returns a string representation of the token.
     *
     * @return string A string representation of the token
     */

    public function __toString()
    {
        return sprintf('%s(%s)', self::typeToString($this->_type, true), $this->_value);
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * Parameters may be:
     * * just type
     * * type and value (or array of possible values)
     * * just value (or array of possible values) (TYPE_NAME is used as type)
     *
     * @param array|int         $type   The type to test
     * @param array|string|null $values The token value
     *
     * @return bool
     */

    public function test($type, $values = null)
    {
        if ($values === null && !is_int($type))
        {
            $values = $type;
            $type = self::TYPE_NAME;
        }

        return ($this->_type === $type) && (
            $values === null ||
            (is_array($values) && in_array($this->_value, $values)) ||
            $this->_value == $values
        );
    }

    /**
     * Gets the line.
     *
     * @return int The source line
     */

    public function getLine()
    {
        return $this->_lineno;
    }

    /**
     * Gets the token type.
     *
     * @return int The token type
     */

    public function getType()
    {
        return $this->_type;
    }

    /**
     * Gets the token value.
     *
     * @return string The token value
     */

    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Returns the constant representation (internal) of a given type.
     *
     * @param int  $type  The type as an integer
     * @param bool $short Whether to return a short representation or not
     *
     * @return string The string representation
     */

    public static function typeToString($type, $short = false)
    {
        switch ($type)
        {
            case self::TYPE_EOF:
                $name = 'TYPE_EOF';
                break;
            case self::TYPE_TEXT:
                $name = 'TYPE_TEXT';
                break;
            case self::TYPE_BLOCK_START:
                $name = 'TYPE_BLOCK_START';
                break;
            case self::TYPE_VAR_START:
                $name = 'TYPE_VAR_START';
                break;
            case self::TYPE_BLOCK_END:
                $name = 'TYPE_BLOCK_END';
                break;
            case self::TYPE_VAR_END:
                $name = 'TYPE_VAR_END';
                break;
            case self::TYPE_NAME:
                $name = 'TYPE_NAME';
                break;
            case self::TYPE_NUMBER:
                $name = 'TYPE_NUMBER';
                break;
            case self::TYPE_STRING:
                $name = 'TYPE_STRING';
                break;
            case self::TYPE_OPERATOR:
                $name = 'TYPE_OPERATOR';
                break;
            case self::TYPE_PUNCTUATION:
                $name = 'TYPE_PUNCTUATION';
                break;
            case self::TYPE_INTERPOLATION_START:
                $name = 'TYPE_INTERPOLATION_START';
                break;
            case self::TYPE_INTERPOLATION_END:
                $name = 'TYPE_INTERPOLATION_END';
                break;
            default:
                throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
        }

        return $short ? $name : 'Apishka_Templater_Token::' . $name;
    }

    /**
     * Returns the english representation of a given type.
     *
     * @param int $type The type as an integer
     *
     * @return string The string representation
     */

    public static function typeToEnglish($type)
    {
        switch ($type)
        {
            case self::TYPE_EOF:
                return 'end of template';
            case self::TYPE_TEXT:
                return 'text';
            case self::TYPE_BLOCK_START:
                return 'begin of statement block';
            case self::TYPE_VAR_START:
                return 'begin of print statement';
            case self::TYPE_BLOCK_END:
                return 'end of statement block';
            case self::TYPE_VAR_END:
                return 'end of print statement';
            case self::TYPE_NAME:
                return 'name';
            case self::TYPE_NUMBER:
                return 'number';
            case self::TYPE_STRING:
                return 'string';
            case self::TYPE_OPERATOR:
                return 'operator';
            case self::TYPE_PUNCTUATION:
                return 'punctuation';
            case self::TYPE_INTERPOLATION_START:
                return 'begin of string interpolation';
            case self::TYPE_INTERPOLATION_END:
                return 'end of string interpolation';
            default:
                throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
        }
    }
}
