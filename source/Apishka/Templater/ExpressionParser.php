<?php

/**
 * Apishka templater expression parser
 *
 * @uses Apishka\EasyExtend\Helper\ByClassNameTrait
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_ExpressionParser
{
    /**
     * Traits
     */

    use Apishka\EasyExtend\Helper\ByClassNameTrait;

    /**
     * Constants
     *
     * @type int
     */

    const OPERATOR_LEFT     = 1;
    const OPERATOR_RIGHT    = 2;

    /**
     * Parser
     *
     * @var Apishka_Templater_Parser
     */

    private $_parser;

    /**
     * Construct
     *
     * @param Apishka_Templater_Parser $parser
     */

    public function __construct(Apishka_Templater_Parser $parser)
    {
        $this->_parser = $parser;
    }

    /**
     * Parse expression
     *
     * @param int $precedence
     * @return Apishka_Templater_NodeAbstract
     */

    public function parseExpression($precedence = 0)
    {
        $expr = $this->getPrimary();
        $token = $this->getParser()->getCurrentToken();

        while ($this->isBinary($token) && $this->getBinaryOperators()[$token->getValue()]['precedence'] >= $precedence)
        {
            $operator = $this->getBinaryOperators()[$token->getValue()];
            $this->getParser()->getStream()->next();

            $expr1 = $this->parseExpression(
                $operator['associativity'] === self::OPERATOR_LEFT
                    ? $operator['precedence'] + 1
                    : $operator['precedence']
            );

            $class = $operator['class'];
            $expr = new $class($expr, $expr1, $token->getLine());

            $token = $this->getParser()->getCurrentToken();
        }

        if ($precedence === 0)
            return $this->parseConditionalExpression($expr);

        return $expr;
    }

    /**
     * Get primary
     *
     * @return Apishka_Templater_NodeAbstract
     */

    private function getPrimary()
    {
        $token = $this->getParser()->getCurrentToken();

        if ($this->isUnary($token))
        {
            $operator = $this->getUnaryOperators()[$token->getValue()];
            $this->getParser()->getStream()->next();
            $expr = $this->parseExpression($operator['precedence']);
            $class = $operator['class'];

            return $this->parsePostfixExpression(new $class($expr, $token->getLine()));
        }
        elseif ($token->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '('))
        {
            $this->getParser()->getStream()->next();
            $expr = $this->parseExpression();
            $this->getParser()->getStream()->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    /**
     * Parse conditional expression
     *
     * @param Apishka_Templater_NodeAbstract $expr
     * @return Apishka_Templater_NodeAbstract
     */

    private function parseConditionalExpression($expr)
    {
        while ($this->getParser()->getStream()->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, '?'))
        {
            if (!$this->getParser()->getStream()->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, ':'))
            {
                $expr2 = $this->parseExpression();
                if ($this->getParser()->getStream()->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, ':'))
                {
                    $expr3 = $this->parseExpression();
                }
                else
                {
                    $expr3 = new Apishka_Templater_Node_Expression_Constant(
                        '',
                        $this->getParser()->getCurrentToken()->getLine()
                    );
                }
            }
            else
            {
                $expr2 = $expr;
                $expr3 = $this->parseExpression();
            }

            $expr = new Apishka_Templater_Node_Expression_Conditional(
                $expr,
                $expr2,
                $expr3,
                $this->getParser()->getCurrentToken()->getLine()
            );
        }

        return $expr;
    }

    /**
     * Is unary
     *
     * @param Apishka_Templater_Token $token
     * @return bool
     */

    private function isUnary(Apishka_Templater_Token $token)
    {
        return $token->test(Apishka_Templater_Token::TYPE_OPERATOR) && isset($this->getUnaryOperators()[$token->getValue()]);
    }

    /**
     * Is binary
     *
     * @param Apishka_Templater_Token $token
     * @return bool
     */

    private function isBinary(Apishka_Templater_Token $token)
    {
        return $token->test(Apishka_Templater_Token::TYPE_OPERATOR) && isset($this->getBinaryOperators()[$token->getValue()]);
    }

    /**
     * Parse primary expression
     *
     * @return Apishka_Templater_NodeAbstract
     */

    public function parsePrimaryExpression()
    {
        $token = $this->getParser()->getCurrentToken();
        switch ($token->getType())
        {
            case Apishka_Templater_Token::TYPE_NAME:
                $this->getParser()->getStream()->next();
                switch ($token->getValue())
                {
                    case 'true':
                    case 'TRUE':
                        $node = new Apishka_Templater_Node_Expression_Constant(true, $token->getLine());
                        break;

                    case 'false':
                    case 'FALSE':
                        $node = new Apishka_Templater_Node_Expression_Constant(false, $token->getLine());
                        break;

                    case 'none':
                    case 'NONE':
                    case 'null':
                    case 'NULL':
                        $node = new Apishka_Templater_Node_Expression_Constant(null, $token->getLine());
                        break;

                    default:
                        if ('(' === $this->getParser()->getCurrentToken()->getValue())
                        {
                            $node = $this->getFunctionNode($token->getValue(), $token->getLine());
                        }
                        else
                        {
                            $node = new Apishka_Templater_Node_Expression_Name($token->getValue(), $token->getLine());
                        }
                }
                break;

            case Apishka_Templater_Token::TYPE_NUMBER:
                $this->getParser()->getStream()->next();
                $node = new Apishka_Templater_Node_Expression_Constant($token->getValue(), $token->getLine());
                break;

            case Apishka_Templater_Token::TYPE_STRING:
            case Apishka_Templater_Token::TYPE_INTERPOLATION_START:
                $node = $this->parseStringExpression();
                break;

            case Apishka_Templater_Token::TYPE_OPERATOR:
                if (preg_match(Apishka_Templater_Lexer::REGEX_NAME, $token->getValue(), $matches) && $matches[0] == $token->getValue())
                {
                    // in this context, string operators are variable names
                    $this->getParser()->getStream()->next();
                    $node = new Apishka_Templater_Node_Expression_Name($token->getValue(), $token->getLine());
                    break;
                }
                elseif (isset($this->getUnaryOperators()[$token->getValue()]))
                {
                    $class = $this->getUnaryOperators()[$token->getValue()]['class'];

                    $ref = new ReflectionClass($class);
                    $negClass = 'Apishka_Templater_Node_Expression_Unary_Neg';
                    $posClass = 'Apishka_Templater_Node_Expression_Unary_Pos';

                    if (!(in_array($ref->getName(), array($negClass, $posClass)) || $ref->isSubclassOf($negClass) || $ref->isSubclassOf($posClass)))
                    {
                        throw new Apishka_Templater_Exception_Syntax(sprintf('Unexpected unary operator "%s"', $token->getValue()), $token->getLine(), $this->getParser()->getFilename());
                    }

                    $this->getParser()->getStream()->next();
                    $expr = $this->parsePrimaryExpression();

                    $node = new $class($expr, $token->getLine());
                    break;
                }

            default:
                if ($token->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '['))
                {
                    $node = $this->parseArrayExpression();
                }
                elseif ($token->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '{'))
                {
                    $node = $this->parseHashExpression();
                }
                else
                {
                    throw new Apishka_Templater_Exception_Syntax(
                        sprintf(
                            'Unexpected token "%s" of value "%s"',
                            Apishka_Templater_Token::typeToEnglish($token->getType()),
                            $token->getValue()
                        ),
                        $token->getLine(),
                        $this->getParser()->getFilename()
                    );
                }
        }

        return $this->parsePostfixExpression($node);
    }

    public function parseStringExpression()
    {
        $stream = $this->getParser()->getStream();

        $nodes = array();
        // a string cannot be followed by another string in a single expression
        $nextCanBeString = true;
        while (true) {
            if ($nextCanBeString && $token = $stream->nextIf(Apishka_Templater_Token::TYPE_STRING)) {
                $nodes[] = new Apishka_Templater_Node_Expression_Constant($token->getValue(), $token->getLine());
                $nextCanBeString = false;
            } elseif ($stream->nextIf(Apishka_Templater_Token::TYPE_INTERPOLATION_START)) {
                $nodes[] = $this->parseExpression();
                $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
                $nextCanBeString = true;
            } else {
                break;
            }
        }

        $expr = array_shift($nodes);
        foreach ($nodes as $node) {
            $expr = new Apishka_Templater_Node_Expression_Binary_Concat($expr, $node, $node->getLine());
        }

        return $expr;
    }

    public function parseArrayExpression()
    {
        $stream = $this->getParser()->getStream();
        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, '[', 'An array element was expected');

        $node = new Apishka_Templater_Node_Expression_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, ']')) {
            if (!$first) {
                $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ',', 'An array element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, ']')) {
                    break;
                }
            }
            $first = false;

            $node->addElement($this->parseExpression());
        }
        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ']', 'An opened array is not properly closed');

        return $node;
    }

    public function parseHashExpression()
    {
        $stream = $this->getParser()->getStream();
        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, '{', 'A hash element was expected');

        $node = new Apishka_Templater_Node_Expression_Array(array(), $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '}')) {
            if (!$first) {
                $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ',', 'A hash value must be followed by a comma');

                // trailing ,?
                if ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '}')) {
                    break;
                }
            }
            $first = false;

            // a hash key can be:
            //
            //  * a number -- 12
            //  * a string -- 'a'
            //  * a name, which is equivalent to a string -- a
            //  * an expression, which must be enclosed in parentheses -- (1 + 2)
            if (($token = $stream->nextIf(Apishka_Templater_Token::TYPE_STRING)) || ($token = $stream->nextIf(Apishka_Templater_Token::TYPE_NAME)) || $token = $stream->nextIf(Apishka_Templater_Token::TYPE_NUMBER)) {
                $key = new Apishka_Templater_Node_Expression_Constant($token->getValue(), $token->getLine());
            } elseif ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '(')) {
                $key = $this->parseExpression();
            } else {
                $current = $stream->getCurrent();

                throw new Apishka_Templater_Exception_Syntax(sprintf('A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s"', Apishka_Templater_Token::typeToEnglish($current->getType()), $current->getValue()), $current->getLine(), $this->getParser()->getFilename());
            }

            $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ':', 'A hash key must be followed by a colon (:)');
            $value = $this->parseExpression();

            $node->addElement($value, $key);
        }
        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, '}', 'An opened hash is not properly closed');

        return $node;
    }

    public function parsePostfixExpression($node)
    {
        while (true) {
            $token = $this->getParser()->getCurrentToken();
            if ($token->getType() == Apishka_Templater_Token::TYPE_PUNCTUATION) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }

    public function getFunctionNode($name, $line)
    {
        switch ($name) {
            case 'parent':
                $this->parseArguments();
                if (!count($this->getParser()->getBlockStack())) {
                    throw new Apishka_Templater_Exception_Syntax('Calling "parent" outside a block is forbidden', $line, $this->getParser()->getFilename());
                }

                if (!$this->getParser()->getParent() && !$this->getParser()->hasTraits()) {
                    throw new Apishka_Templater_Exception_Syntax('Calling "parent" on a template that does not extend nor "use" another template is forbidden', $line, $this->getParser()->getFilename());
                }

                return new Apishka_Templater_Node_Expression_Parent($this->getParser()->peekBlockStack(), $line);
            case 'block':
                return new Apishka_Templater_Node_Expression_BlockReference($this->parseArguments()->getNode(0), false, $line);
            case 'attribute':
                $args = $this->parseArguments();
                if (count($args) < 2) {
                    throw new Apishka_Templater_Exception_Syntax('The "attribute" function takes at least two arguments (the variable and the attributes)', $line, $this->getParser()->getFilename());
                }

                return new Apishka_Templater_Node_Expression_GetAttr($args->getNode(0), $args->getNode(1), count($args) > 2 ? $args->getNode(2) : null, Apishka_Templater_Template::ANY_CALL, $line);
            default:
                if (null !== $alias = $this->getParser()->getImportedSymbol('function', $name)) {
                    $arguments = new Apishka_Templater_Node_Expression_Array(array(), $line);
                    foreach ($this->parseArguments() as $n) {
                        $arguments->addElement($n);
                    }

                    $node = new Apishka_Templater_Node_Expression_MethodCall($alias['node'], $alias['name'], $arguments, $line);
                    $node->setAttribute('safe', true);

                    return $node;
                }

                $args = $this->parseArguments(true);
                $class = $this->getFunctionNodeClass($name, $line);

                return new $class($name, $args, $line);
        }
    }

    public function parseSubscriptExpression($node)
    {
        $stream = $this->getParser()->getStream();
        $token = $stream->next();
        $lineno = $token->getLine();
        $arguments = new Apishka_Templater_Node_Expression_Array(array(), $lineno);
        $type = Apishka_Templater_Template::ANY_CALL;
        if ($token->getValue() == '.') {
            $token = $stream->next();
            if (
                $token->getType() == Apishka_Templater_Token::TYPE_NAME
                ||
                $token->getType() == Apishka_Templater_Token::TYPE_NUMBER
                ||
                ($token->getType() == Apishka_Templater_Token::TYPE_OPERATOR && preg_match(Apishka_Templater_Lexer::REGEX_NAME, $token->getValue()))
            ) {
                $arg = new Apishka_Templater_Node_Expression_Constant($token->getValue(), $lineno);

                if ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '(')) {
                    $type = Apishka_Templater_Template::METHOD_CALL;
                    foreach ($this->parseArguments() as $n) {
                        $arguments->addElement($n);
                    }
                }
            } else {
                throw new Apishka_Templater_Exception_Syntax('Expected name or number', $lineno, $this->getParser()->getFilename());
            }

            if ($node instanceof Apishka_Templater_Node_Expression_Name && null !== $this->getParser()->getImportedSymbol('template', $node->getAttribute('name'))) {
                if (!$arg instanceof Apishka_Templater_Node_Expression_Constant) {
                    throw new Apishka_Templater_Exception_Syntax(sprintf('Dynamic macro names are not supported (called on "%s")', $node->getAttribute('name')), $token->getLine(), $this->getParser()->getFilename());
                }

                $name = $arg->getAttribute('value');

                $node = new Apishka_Templater_Node_Expression_MethodCall($node, 'macro_'.$name, $arguments, $lineno);
                $node->setAttribute('safe', true);

                return $node;
            }
        } else {
            $type = Apishka_Templater_Template::ARRAY_CALL;

            // slice?
            $slice = false;
            if ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, ':')) {
                $slice = true;
                $arg = new Apishka_Templater_Node_Expression_Constant(0, $token->getLine());
            } else {
                $arg = $this->parseExpression();
            }

            if ($stream->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, ':')) {
                $slice = true;
            }

            if ($slice) {
                if ($stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, ']')) {
                    $length = new Apishka_Templater_Node_Expression_Constant(null, $token->getLine());
                } else {
                    $length = $this->parseExpression();
                }

                $class = $this->getFilterNodeClass('slice', $token->getLine());
                $arguments = new Apishka_Templater_Node(array($arg, $length));
                $filter = new $class($node, new Apishka_Templater_Node_Expression_Constant('slice', $token->getLine()), $arguments, $token->getLine());

                $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ']');

                return $filter;
            }

            $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ']');
        }

        return new Apishka_Templater_Node_Expression_GetAttr($node, $arg, $arguments, $type, $lineno);
    }

    public function parseFilterExpression($node)
    {
        $this->getParser()->getStream()->next();

        return $this->parseFilterExpressionRaw($node);
    }

    public function parseFilterExpressionRaw($node, $tag = null)
    {
        while (true) {
            $token = $this->getParser()->getStream()->expect(Apishka_Templater_Token::TYPE_NAME);

            $name = new Apishka_Templater_Node_Expression_Constant($token->getValue(), $token->getLine());
            if (!$this->getParser()->getStream()->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '(')) {
                $arguments = new Apishka_Templater_Node();
            } else {
                $arguments = $this->parseArguments(true);
            }

            $class = $this->getFilterNodeClass($name->getAttribute('value'), $token->getLine());

            $node = new $class($node, $name, $arguments, $token->getLine(), $tag);

            if (!$this->getParser()->getStream()->test(Apishka_Templater_Token::TYPE_PUNCTUATION, '|')) {
                break;
            }

            $this->getParser()->getStream()->next();
        }

        return $node;
    }

    /**
     * Parses arguments.
     *
     * @param bool $namedArguments Whether to allow named arguments or not
     * @param bool $definition     Whether we are parsing arguments for a function definition
     *
     * @return Apishka_Templater_Node
     *
     * @throws Apishka_Templater_Exception_Syntax
     */
    public function parseArguments($namedArguments = false, $definition = false)
    {
        $args = array();
        $stream = $this->getParser()->getStream();

        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, '(', 'A list of arguments must begin with an opening parenthesis');
        while (!$stream->test(Apishka_Templater_Token::TYPE_PUNCTUATION, ')')) {
            if (!empty($args)) {
                $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ',', 'Arguments must be separated by a comma');
            }

            if ($definition) {
                $token = $stream->expect(Apishka_Templater_Token::TYPE_NAME, null, 'An argument must be a name');
                $value = new Apishka_Templater_Node_Expression_Name($token->getValue(), $this->getParser()->getCurrentToken()->getLine());
            } else {
                $value = $this->parseExpression();
            }

            $name = null;
            if ($namedArguments && $token = $stream->nextIf(Apishka_Templater_Token::TYPE_OPERATOR, '=')) {
                if (!$value instanceof Apishka_Templater_Node_Expression_Name) {
                    throw new Apishka_Templater_Exception_Syntax(sprintf('A parameter name must be a string, "%s" given', get_class($value)), $token->getLine(), $this->getParser()->getFilename());
                }
                $name = $value->getAttribute('name');

                if ($definition) {
                    $value = $this->parsePrimaryExpression();

                    if (!$this->checkConstantExpression($value)) {
                        throw new Apishka_Templater_Exception_Syntax(sprintf('A default value for an argument must be a constant (a boolean, a string, a number, or an array).'), $token->getLine(), $this->getParser()->getFilename());
                    }
                } else {
                    $value = $this->parseExpression();
                }
            }

            if ($definition) {
                if (null === $name) {
                    $name = $value->getAttribute('name');
                    $value = new Apishka_Templater_Node_Expression_Constant(null, $this->getParser()->getCurrentToken()->getLine());
                }
                $args[$name] = $value;
            } else {
                if (null === $name) {
                    $args[] = $value;
                } else {
                    $args[$name] = $value;
                }
            }
        }
        $stream->expect(Apishka_Templater_Token::TYPE_PUNCTUATION, ')', 'A list of arguments must be closed by a parenthesis');

        return new Apishka_Templater_Node($args);
    }

    public function parseAssignmentExpression()
    {
        $targets = array();
        while (true) {
            $token = $this->getParser()->getStream()->expect(Apishka_Templater_Token::TYPE_NAME, null, 'Only variables can be assigned to');
            if (in_array($token->getValue(), array('true', 'false', 'none'))) {
                throw new Apishka_Templater_Exception_Syntax(sprintf('You cannot assign a value to "%s"', $token->getValue()), $token->getLine(), $this->getParser()->getFilename());
            }
            $targets[] = new Apishka_Templater_Node_Expression_AssignName($token->getValue(), $token->getLine());

            if (!$this->getParser()->getStream()->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, ',')) {
                break;
            }
        }

        return new Apishka_Templater_Node($targets);
    }

    public function parseMultitargetExpression()
    {
        $targets = array();
        while (true) {
            $targets[] = $this->parseExpression();
            if (!$this->getParser()->getStream()->nextIf(Apishka_Templater_Token::TYPE_PUNCTUATION, ',')) {
                break;
            }
        }

        return new Apishka_Templater_Node($targets);
    }

    private function getFunctionNodeClass($name, $line)
    {
        $env = $this->getParser()->getEnvironment();

        if (false === $function = $env->getFunction($name)) {
            $e = new Apishka_Templater_Exception_Syntax(sprintf('Unknown "%s" function.', $name), $line, $this->getParser()->getFilename());
            $e->addSuggestions($name, array_keys($env->getFunctions()));

            throw $e;
        }

        if ($function->isDeprecated()) {
            $message = sprintf('Twig Function "%s" is deprecated', $function->getName());
            if ($function->getAlternative()) {
                $message .= sprintf('. Use "%s" instead', $function->getAlternative());
            }
            $message .= sprintf(' in %s at line %d.', $this->getParser()->getFilename(), $line);

            @trigger_error($message, E_USER_DEPRECATED);
        }

        return $function->getNodeClass();
    }

    private function getFilterNodeClass($name, $line)
    {
        $env = $this->getParser()->getEnvironment();

        if (false === $filter = $env->getFilter($name)) {
            $e = new Apishka_Templater_Exception_Syntax(sprintf('Unknown "%s" filter.', $name), $line, $this->getParser()->getFilename());
            $e->addSuggestions($name, array_keys($env->getFilters()));

            throw $e;
        }

        if ($filter->isDeprecated()) {
            $message = sprintf('Twig Filter "%s" is deprecated', $filter->getName());
            if ($filter->getAlternative()) {
                $message .= sprintf('. Use "%s" instead', $filter->getAlternative());
            }
            $message .= sprintf(' in %s at line %d.', $this->getParser()->getFilename(), $line);

            @trigger_error($message, E_USER_DEPRECATED);
        }

        return $filter->getNodeClass();
    }

    // checks that the node only contains "constant" elements
    private function checkConstantExpression(Apishka_Templater_Node $node)
    {
        if (!($node instanceof Apishka_Templater_Node_Expression_Constant || $node instanceof Apishka_Templater_Node_Expression_Array
            || $node instanceof Apishka_Templater_Node_Expression_Unary_Neg || $node instanceof Apishka_Templater_Node_Expression_Unary_Pos
        )) {
            return false;
        }

        foreach ($node as $n)
        {
            if (!$this->checkConstantExpression($n))
                return false;
        }

        return true;
    }

    /**
     * Get parser
     *
     * @return Apishka_Templater_Parser
     */

    protected function getParser()
    {
        return $this->_parser;
    }

    /**
     * Get binary operators
     *
     * @return array
     */

    protected function getBinaryOperators()
    {
        return Apishka_Templater_Manager::getInstance()->getBinaryOperators();
    }

    /**
     * Get unary operators
     *
     * @return array
     */

    protected function getUnaryOperators()
    {
        return Apishka_Templater_Manager::getInstance()->getUnaryOperators();
    }
}
