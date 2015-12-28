<?php

/**
 * Lexes a template string.
 *
 * @author Evgeny Reykh <evgeny@reykh.com>
 */

class Apishka_Templater_Lexer
{
    /**
     * Traits
     */

    use Apishka\EasyExtend\Helper\ByClassNameTrait;

    /**
     * Tokens
     *
     * @var array
     */

    protected $_tokens;

    /**
     * Code
     *
     * @var string
     */

    protected $_code;

    /**
     * Cursor
     *
     * @var mixed
     */

    protected $_cursor;

    /**
     * Line number
     *
     * @var mixed
     */

    protected $_lineno;

    /**
     * End
     *
     * @var mixed
     */

    protected $_end;

    /**
     * State
     *
     * @var mixed
     */

    protected $_state;

    /**
     * States
     *
     * @var mixed
     */

    protected $_states;

    /**
     * Brackets
     *
     * @var mixed
     */

    protected $_brackets;

    /**
     * Filename
     *
     * @var mixed
     */

    protected $_filename;

    /**
     * Options
     *
     * @var mixed
     */

    protected $_options;

    /**
     * Regexes
     *
     * @var mixed
     */

    protected $_regexes;

    /**
     * Position
     *
     * @var mixed
     */

    protected $_position;

    /**
     * Positions
     *
     * @var mixed
     */

    protected $_positions;

    /**
     * Current var block line
     *
     * @var mixed
     */

    protected $_current_var_block_line;

    /**
     * State constants
     */

    const STATE_DATA            = 0;
    const STATE_BLOCK           = 1;
    const STATE_VAR             = 2;
    const STATE_STRING          = 3;
    const STATE_INTERPOLATION   = 4;

    /**
     * Regexp constants
     */

    const REGEX_NAME            = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
    const REGEX_NUMBER          = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING          = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const REGEX_DQ_STRING_DELIM = '/"/A';
    const REGEX_DQ_STRING_PART  = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    const PUNCTUATION           = '()[]{}?:.,|';

    /**
     * Construct
     *
     * @param array $options
     */

    public function __construct(array $options = array())
    {
        $this->_options = array_replace(
            array(
                'tag_comment'     => array('{#', '#}'),
                'tag_block'       => array('{%', '%}'),
                'tag_variable'    => array('{{', '}}'),
                'whitespace_trim' => '-',
                'interpolation'   => array('#{', '}'),
            ),
            $options
        );

        $this->_regexes = array(
            'lex_var'             => '/\s*' . preg_quote($this->_options['whitespace_trim'] . $this->_options['tag_variable'][1], '/') . '\s*|\s*' . preg_quote($this->_options['tag_variable'][1], '/') . '/A',
            'lex_block'           => '/\s*(?:' . preg_quote($this->_options['whitespace_trim'] . $this->_options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->_options['tag_block'][1], '/') . ')\n?/A',
            'lex_raw_data'        => '/(' . preg_quote($this->_options['tag_block'][0] . $this->_options['whitespace_trim'], '/') . '|' . preg_quote($this->_options['tag_block'][0], '/') . ')\s*(?:end%s)\s*(?:' . preg_quote($this->_options['whitespace_trim'] . $this->_options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->_options['tag_block'][1], '/') . ')/s',
            'operator'            => $this->getOperatorRegex(),
            'lex_comment'         => '/(?:' . preg_quote($this->_options['whitespace_trim'], '/') . preg_quote($this->_options['tag_comment'][1], '/') . '\s*|' . preg_quote($this->_options['tag_comment'][1], '/') . ')\n?/s',
            'lex_block_raw'       => '/\s*(raw|verbatim)\s*(?:' . preg_quote($this->_options['whitespace_trim'] . $this->_options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->_options['tag_block'][1], '/') . ')/As',
            'lex_block_line'      => '/\s*line\s+(\d+)\s*' . preg_quote($this->_options['tag_block'][1], '/') . '/As',
            'lex_tokens_start'    => '/(' . preg_quote($this->_options['tag_variable'][0], '/') . '|' . preg_quote($this->_options['tag_block'][0], '/') . '|' . preg_quote($this->_options['tag_comment'][0], '/') . ')(' . preg_quote($this->_options['whitespace_trim'], '/') . ')?/s',
            'interpolation_start' => '/' . preg_quote($this->_options['interpolation'][0], '/') . '\s*/A',
            'interpolation_end'   => '/\s*' . preg_quote($this->_options['interpolation'][1], '/') . '/A',
        );
    }

    /**
     * Tokenize
     *
     * @param string $code
     * @param string $filename
     */

    public function tokenize($code, $filename = null)
    {
        $this->_code        = str_replace(array("\r\n", "\r"), "\n", $code);
        $this->_filename    = $filename;
        $this->_cursor      = 0;
        $this->_lineno      = 1;
        $this->_end         = strlen($this->_code);
        $this->_tokens      = array();
        $this->_state       = self::STATE_DATA;
        $this->_states      = array();
        $this->_brackets    = array();
        $this->_position    = -1;

        // find all token starts in one go
        preg_match_all($this->_regexes['lex_tokens_start'], $this->_code, $matches, PREG_OFFSET_CAPTURE);
        $this->_positions = $matches;

        while ($this->_cursor < $this->_end)
        {
            // dispatch to the lexing functions depending
            // on the current state
            switch ($this->_state)
            {
                case self::STATE_DATA:
                {
                    $this->lexData();
                    break;
                }

                case self::STATE_BLOCK:
                {
                    $this->lexBlock();
                    break;
                }

                case self::STATE_VAR:
                {
                    $this->lexVar();
                    break;
                }

                case self::STATE_STRING:
                {
                    $this->lexString();
                    break;
                }

                case self::STATE_INTERPOLATION:
                {
                    $this->lexInterpolation();
                    break;
                }
            }
        }

        $this->pushToken(Apishka_Templater_Token::TYPE_EOF);

        if (!empty($this->_brackets))
        {
            list($expect, $lineno) = array_pop($this->_brackets);

            throw new Apishka_Templater_Exception_Syntax(sprintf('Unclosed "%s"', $expect), $lineno, $this->_filename);
        }

        return new Apishka_Templater_TokenStream($this->_tokens, $this->_filename);
    }

    /**
     * Lex data
     */

    protected function lexData()
    {
        // if no matches are left we return the rest of the template as simple text token
        if ($this->_position == count($this->_positions[0]) - 1)
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_TEXT, substr($this->_code, $this->_cursor));
            $this->_cursor = $this->_end;

            return;
        }

        // Find the first token after the current cursor
        $position = $this->_positions[0][++$this->_position];
        while ($position[1] < $this->_cursor)
        {
            if ($this->_position == count($this->_positions[0]) - 1)
            {
                return;
            }

            $position = $this->_positions[0][++$this->_position];
        }

        // push the template text first
        $text = $textContent = substr($this->_code, $this->_cursor, $position[1] - $this->_cursor);
        if (isset($this->_positions[2][$this->_position][0]))
        {
            $text = rtrim($text);
        }

        $this->pushToken(Apishka_Templater_Token::TYPE_TEXT, $text);
        $this->moveCursor($textContent . $position[0]);

        switch ($this->_positions[1][$this->_position][0])
        {
            case $this->_options['tag_comment'][0]:
            {
                $this->lexComment();
                break;
            }

            case $this->_options['tag_block'][0]:
            {
                // raw data?
                if (preg_match($this->_regexes['lex_block_raw'], $this->_code, $match, null, $this->_cursor))
                {
                    $this->moveCursor($match[0]);
                    $this->lexRawData($match[1]);
                }
                // {% line \d+ %}
                elseif (preg_match($this->_regexes['lex_block_line'], $this->_code, $match, null, $this->_cursor))
                {
                    $this->moveCursor($match[0]);
                    $this->_lineno = (int) $match[1];
                }
                else
                {
                    $this->pushToken(Apishka_Templater_Token::TYPE_BLOCK_START);
                    $this->pushState(self::STATE_BLOCK);
                    $this->_current_var_block_line = $this->_lineno;
                }

                break;
            }

            case $this->_options['tag_variable'][0]:
            {
                $this->pushToken(Apishka_Templater_Token::TYPE_VAR_START);
                $this->pushState(self::STATE_VAR);
                $this->_current_var_block_line = $this->_lineno;

                break;
            }
        }
    }

    /**
     * Lex block
     */

    protected function lexBlock()
    {
        if (empty($this->_brackets) && preg_match($this->_regexes['lex_block'], $this->_code, $match, null, $this->_cursor))
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_BLOCK_END);
            $this->moveCursor($match[0]);
            $this->popState();
        }
        else
        {
            $this->lexExpression();
        }
    }

    /**
     * Lex var
     */

    protected function lexVar()
    {
        if (empty($this->_brackets) && preg_match($this->_regexes['lex_var'], $this->_code, $match, null, $this->_cursor))
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_VAR_END);
            $this->moveCursor($match[0]);
            $this->popState();
        }
        else
        {
            $this->lexExpression();
        }
    }

    /**
     * Lex expression
     */

    protected function lexExpression()
    {
        // whitespace
        if (preg_match('/\s+/A', $this->_code, $match, null, $this->_cursor))
        {
            $this->moveCursor($match[0]);

            if ($this->_cursor >= $this->_end)
            {
                throw new Apishka_Templater_Exception_Syntax(sprintf('Unclosed "%s"', $this->_state === self::STATE_BLOCK ? 'block' : 'variable'), $this->_current_var_block_line, $this->_filename);
            }
        }

        // operators
        if (preg_match($this->_regexes['operator'], $this->_code, $match, null, $this->_cursor))
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_OPERATOR, preg_replace('/\s+/', ' ', $match[0]));
            $this->moveCursor($match[0]);
        }
        // names
        elseif (preg_match(self::REGEX_NAME, $this->_code, $match, null, $this->_cursor))
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_NAME, $match[0]);
            $this->moveCursor($match[0]);
        }
        // numbers
        elseif (preg_match(self::REGEX_NUMBER, $this->_code, $match, null, $this->_cursor))
        {
            $number = (float) $match[0];  // floats
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX)
            {
                $number = (int) $match[0]; // integers lower than the maximum
            }

            $this->pushToken(Apishka_Templater_Token::TYPE_NUMBER, $number);
            $this->moveCursor($match[0]);
        }
        // punctuation
        elseif (false !== strpos(self::PUNCTUATION, $this->_code[$this->_cursor]))
        {
            // opening bracket
            if (false !== strpos('([{', $this->_code[$this->_cursor]))
            {
                $this->_brackets[] = array($this->_code[$this->_cursor], $this->_lineno);
            }
            // closing bracket
            elseif (false !== strpos(')]}', $this->_code[$this->_cursor]))
            {
                if (empty($this->_brackets))
                {
                    throw new Apishka_Templater_Exception_Syntax(sprintf('Unexpected "%s"', $this->_code[$this->_cursor]), $this->_lineno, $this->_filename);
                }

                list($expect, $lineno) = array_pop($this->_brackets);
                if ($this->_code[$this->_cursor] != strtr($expect, '([{', ')]}'))
                {
                    throw new Apishka_Templater_Exception_Syntax(sprintf('Unclosed "%s"', $expect), $lineno, $this->_filename);
                }
            }

            $this->pushToken(Apishka_Templater_Token::TYPE_PUNCTUATION, $this->_code[$this->_cursor]);
            ++$this->_cursor;
        }
        // strings
        elseif (preg_match(self::REGEX_STRING, $this->_code, $match, null, $this->_cursor))
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_STRING, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        }
        // opening double quoted string
        elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->_code, $match, null, $this->_cursor))
        {
            $this->_brackets[] = array('"', $this->_lineno);
            $this->pushState(self::STATE_STRING);
            $this->moveCursor($match[0]);
        }
        // unlexable
        else
        {
            throw new Apishka_Templater_Exception_Syntax(sprintf('Unexpected character "%s"', $this->_code[$this->_cursor]), $this->_lineno, $this->_filename);
        }
    }

    /**
     * Lex raw data
     *
     * @param string $tag
     */

    protected function lexRawData($tag)
    {
        if (!preg_match(str_replace('%s', $tag, $this->_regexes['lex_raw_data']), $this->_code, $match, PREG_OFFSET_CAPTURE, $this->_cursor))
        {
            throw new Apishka_Templater_Exception_Syntax(sprintf('Unexpected end of file: Unclosed "%s" block', $tag), $this->_lineno, $this->_filename);
        }

        $text = substr($this->_code, $this->_cursor, $match[0][1] - $this->_cursor);
        $this->moveCursor($text . $match[0][0]);

        if (false !== strpos($match[1][0], $this->_options['whitespace_trim']))
        {
            $text = rtrim($text);
        }

        $this->pushToken(Apishka_Templater_Token::TYPE_TEXT, $text);
    }

    /**
     * Lex comment
     */

    protected function lexComment()
    {
        if (!preg_match($this->_regexes['lex_comment'], $this->_code, $match, PREG_OFFSET_CAPTURE, $this->_cursor))
        {
            throw new Apishka_Templater_Exception_Syntax('Unclosed comment', $this->_lineno, $this->_filename);
        }

        $this->moveCursor(substr($this->_code, $this->_cursor, $match[0][1] - $this->_cursor) . $match[0][0]);
    }

    /**
     * Lex string
     */

    protected function lexString()
    {
        if (preg_match($this->_regexes['interpolation_start'], $this->_code, $match, null, $this->_cursor))
        {
            $this->_brackets[] = array($this->_options['interpolation'][0], $this->_lineno);
            $this->pushToken(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
            $this->moveCursor($match[0]);
            $this->pushState(self::STATE_INTERPOLATION);
        }
        elseif (preg_match(self::REGEX_DQ_STRING_PART, $this->_code, $match, null, $this->_cursor) && strlen($match[0]) > 0)
        {
            $this->pushToken(Apishka_Templater_Token::TYPE_STRING, stripcslashes($match[0]));
            $this->moveCursor($match[0]);
        }
        elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->_code, $match, null, $this->_cursor))
        {
            list($expect, $lineno) = array_pop($this->_brackets);
            if ($this->_code[$this->_cursor] != '"')
            {
                throw new Apishka_Templater_Exception_Syntax(sprintf('Unclosed "%s"', $expect), $lineno, $this->_filename);
            }

            $this->popState();
            ++$this->_cursor;
        }
    }

    /**
     * Lex interpolation
     */

    protected function lexInterpolation()
    {
        $bracket = end($this->_brackets);
        if ($this->_options['interpolation'][0] === $bracket[0] && preg_match($this->_regexes['interpolation_end'], $this->_code, $match, null, $this->_cursor))
        {
            array_pop($this->_brackets);
            $this->pushToken(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
            $this->moveCursor($match[0]);
            $this->popState();
        }
        else
        {
            $this->lexExpression();
        }
    }

    /**
     * Push token
     *
     * @param int    $type
     * @param string $value
     */

    protected function pushToken($type, $value = '')
    {
        // do not push empty text tokens
        if ($type === Apishka_Templater_Token::TYPE_TEXT && $value === '')
        {
            return;
        }

        $this->_tokens[] = new Apishka_Templater_Token($type, $value, $this->_lineno);
    }

    /**
     * Move cursor
     *
     * @param string $text
     */

    protected function moveCursor($text)
    {
        $this->_cursor += strlen($text);
        $this->_lineno += substr_count($text, "\n");
    }

    /**
     * Get operator regex
     */

    protected function getOperatorRegex()
    {
        $operators = array_merge(
            array('='),
            $this->getUnaryOperators(),
            $this->getBinaryOperators()
        );

        $operators = array_combine($operators, array_map('strlen', $operators));
        arsort($operators);

        $regex = array();
        foreach ($operators as $operator => $length)
        {
            // an operator that ends with a character must be followed by
            // a whitespace or a parenthesis
            if (ctype_alpha($operator[$length - 1]))
            {
                $r = preg_quote($operator, '/') . '(?=[\s()])';
            }
            else
            {
                $r = preg_quote($operator, '/');
            }

            // an operator with a space can be any amount of whitespaces
            $r = preg_replace('/\s+/', '\s+', $r);

            $regex[] = $r;
        }

        return '/' . implode('|', $regex) . '/A';
    }

    /**
     * Get unary operators
     *
     * @return array
     */

    protected function getUnaryOperators()
    {
        return array_keys(Apishka_Templater_NodeRouter::apishka()->getItemsByType('unary'));
    }

    /**
     * Get binary operators
     *
     * @return array
     */

    protected function getBinaryOperators()
    {
        return array_keys(Apishka_Templater_NodeRouter::apishka()->getItemsByType('binary'));
    }

    /**
     * Push state
     *
     * @param int $state
     */

    protected function pushState($state)
    {
        $this->_states[] = $this->_state;
        $this->_state = $state;
    }

    /**
     * Pop state
     */

    protected function popState()
    {
        if (count($this->_states) === 0)
        {
            throw new Exception('Cannot pop state without a previous state');
        }

        $this->_state = array_pop($this->_states);
    }
}
