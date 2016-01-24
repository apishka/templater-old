<?php

/**
 * Apishka templater parser
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Parser
{
    /**
     * Stack
     *
     * @var array
     */

    private $_stack = array();

    /**
     * Stream
     *
     * @var Apishka_Templater_TokenStream
     */

    private $_stream;

    /**
     * Parent
     *
     * @var mixed
     */

    private $_parent;

    /**
     * Handlers
     *
     * @var mixed
     */

    private $_handlers;

    /**
     * Visitors
     *
     * @var mixed
     */

    private $_visitors;

    /**
     * Expression parser
     *
     * @var mixed
     */

    private $_expressionParser;

    /**
     * Blocks
     *
     * @var mixed
     */

    private $_blocks;

    /**
     * Block stack
     *
     * @var mixed
     */

    private $_blockStack;

    /**
     * Macros
     *
     * @var mixed
     */

    private $_macros;

    /**
     * Imported symbols
     *
     * @var mixed
     */

    private $_importedSymbols;

    /**
     * Traits
     *
     * @var mixed
     */

    private $_traits;

    /**
     * Embedded templates
     *
     * @var array
     */

    private $_embeddedTemplates = array();

    /**
     * Constructor.
     */

    public function __construct()
    {
    }

    /**
     * Get var name
     *
     * @return string
     */

    public function getVarName()
    {
        return sprintf('__internal_%s', hash('sha256', uniqid(mt_rand(), true), false));
    }

    /**
     * Get filename
     *
     * @return Apishka_Templater_TokenStream
     */

    public function getFilename()
    {
        return $this->_stream->getFilename();
    }

    /**
     * Parse
     *
     * @param Apishka_Templater_TokenStream $stream
     * @param mixed                         $test
     * @param bool                          $dropNeedle
     */

    public function parse(Apishka_Templater_TokenStream $stream, $test = null, $dropNeedle = false)
    {
        // push all variables into the stack to keep the current state of the parser
        $vars = get_object_vars($this);
        unset($vars['_stack'], $vars['_handlers'], $vars['_visitors'], $vars['_expressionParser'], $vars['_reservedMacroNames']);
        $this->_stack[] = $vars;

        // tag handlers
        if ($this->_handlers === null)
        {
            $this->_handlers = array();
            foreach ($this->getTokenParsers() as $handler)
            {
                $handler->setParser($this);

                $this->_handlers[$handler->getTag()] = $handler;
            }
        }

        // node visitors
        if ($this->_visitors === null)
            $this->_visitors = $this->getNodeVisitors();

        if ($this->_expressionParser === null)
            $this->_expressionParser = Apishka_Templater_ExpressionParser::Apishka($this);

        $this->_stream              = $stream;
        $this->_parent              = null;
        $this->_blocks              = array();
        $this->_macros              = array();
        $this->_traits              = array();
        $this->_blockStack          = array();
        $this->_importedSymbols     = array(array());
        $this->_embeddedTemplates   = array();

        try
        {
            $body = $this->subparse($test, $dropNeedle);

            if (null !== $this->_parent)
            {
                if (null === $body = $this->filterBodyNodes($body))
                    $body = Apishka_Templater_Node::apishka();
            }
        }
        catch (Apishka_Templater_Exception_Syntax $e)
        {
            if (!$e->getTemplateFile())
                $e->setTemplateFile($this->getFilename());

            if (!$e->getTemplateLine())
                $e->setTemplateLine($this->_stream->getCurrent()->getLine());

            throw $e;
        }

        $node = Apishka_Templater_Node_Module::apishka(
            Apishka_Templater_Node_Body::apishka(array($body)),
            $this->_parent,
            Apishka_Templater_Node::apishka($this->_blocks),
            Apishka_Templater_Node::apishka($this->_macros),
            Apishka_Templater_Node::apishka($this->_traits),
            $this->_embeddedTemplates,
            $this->getFilename()
        );

        $traverser = Apishka_Templater_NodeTraverser::apishka($this->env, $this->_visitors);

        $node = $traverser->traverse($node);

        // restore previous stack so previous parse() call can resume working
        foreach (array_pop($this->stack) as $key => $val) {
            $this->$key = $val;
        }

        return $node;
    }

    public function subparse($test, $dropNeedle = false)
    {
        $lineno = $this->getCurrentToken()->getLine();
        $rv = array();

        while (!$this->_stream->isEOF())
        {
            switch ($this->getCurrentToken()->getType())
            {
                case Apishka_Templater_Token::TYPE_TEXT:
                    $token = $this->_stream->next();
                    $rv[] = Apishka_Templater_Node_Text::apishka($token->getValue(), $token->getLine());
                    break;

                case Apishka_Templater_Token::TYPE_VAR_START:
                    $token = $this->_stream->next();
                    $expr = $this->_expressionParser->parseExpression();
                    $this->_stream->expect(Apishka_Templater_Token::TYPE_VAR_END);
                    $rv[] = Apishka_Templater_Node_Print::apishka($expr, $token->getLine());
                    break;

                case Apishka_Templater_Token::TYPE_BLOCK_START:
                    $this->_stream->next();
                    $token = $this->getCurrentToken();

                    if ($token->getType() !== Apishka_Templater_Token::TYPE_NAME)
                        throw new Apishka_Templater_Exception_Syntax('A block must start with a tag name', $token->getLine(), $this->getFilename());

                    if (null !== $test && $test($token))
                    {
                        if ($dropNeedle)
                            $this->_stream->next();

                        if (1 === count($rv))
                            return $rv[0];

                        return Apishka_Templater_Node::apishka($rv, array(), $lineno);
                    }

                    if (!isset($this->_handlers[$token->getValue()]))
                    {
                        if (null !== $test)
                        {
                            $e = new Apishka_Templater_Exception_Syntax(sprintf('Unexpected "%s" tag', $token->getValue()), $token->getLine(), $this->getFilename());

                            if (is_array($test) && isset($test[0]) && $test[0] instanceof Apishka_Templater_TokenParserInterface)
                                $e->appendMessage(sprintf(' (expecting closing tag for the "%s" tag defined near line %s).', $test[0]->getTag(), $lineno));
                        }
                        else
                        {
                            $e = new Apishka_Templater_Exception_Syntax(sprintf('Unknown "%s" tag.', $token->getValue()), $token->getLine(), $this->getFilename());
                            $e->addSuggestions($token->getValue(), array_keys($this->env->getTags()));
                        }

                        throw $e;
                    }

                    $this->_stream->next();

                    $subparser = $this->_handlers[$token->getValue()];
                    $node = $subparser->parse($token);
                    if (null !== $node)
                        $rv[] = $node;

                    break;

                default:
                    throw new Apishka_Templater_Exception_Syntax('Lexer or parser ended up in unsupported state.', 0, $this->getFilename());
            }
        }

        if (1 === count($rv))
            return $rv[0];

        return Apishka_Templater_Node::apishka($rv, array(), $lineno);
    }

    /**
     * Add handler
     *
     * @param string $name
     * @param mixed  $class
     *
     * @return Apishka_Templater_Parser
     */

    public function addHandler($name, $class)
    {
        $this->_handlers[$name] = $class;

        return $this;
    }

    /**
     * Add node visitor
     *
     * @param Apishka_Templater_NodeVisitorInterface $visitor
     *
     * @return Apishka_Templater_Parser
     */

    public function addNodeVisitor(Apishka_Templater_NodeVisitorInterface $visitor)
    {
        $this->_visitors[] = $visitor;

        return $this;
    }

    /**
     * Get block stack
     *
     * @return array
     */

    public function getBlockStack()
    {
        return $this->_blockStack;
    }

    /**
     * Peek block stack
     *
     * @return mixed
     */

    public function peekBlockStack()
    {
        return $this->_blockStack[count($this->_blockStack) - 1];
    }

    /**
     * Pop block stack
     *
     * @return Apishka_Templater_Parser
     */

    public function popBlockStack()
    {
        array_pop($this->_blockStack);

        return $this;
    }

    /**
     * Push block stack
     *
     * @param string $name
     *
     * @return Apishka_Templater_Parser
     */

    public function pushBlockStack($name)
    {
        $this->_blockStack[] = $name;

        return $this;
    }

    /**
     * Has block
     *
     * @param string $name
     *
     * @return bool
     */

    public function hasBlock($name)
    {
        return isset($this->_blocks[$name]);
    }

    /**
     * Get block
     *
     * @param string $name
     *
     * @return Apishka_Templater_Node_Block
     */

    public function getBlock($name)
    {
        return $this->_blocks[$name];
    }

    /**
     * Set block
     *
     * @param string                       $name
     * @param Apishka_Templater_Node_Block $value
     *
     * @return Apishka_Templater_Parser
     */

    public function setBlock($name, Apishka_Templater_Node_Block $value)
    {
        $this->_blocks[$name] = Apishka_Templater_Node_Body::apishka(array($value), array(), $value->getLine());

        return $this;
    }

    /**
     * Has macro
     *
     * @param string $name
     *
     * @return bool
     */

    public function hasMacro($name)
    {
        return isset($this->_macros[$name]);
    }

    /**
     * Set macro
     *
     * @param string                       $name
     * @param Apishka_Templater_Node_Macro $node
     *
     * @return Apishka_Templater_Parser
     */

    public function setMacro($name, Apishka_Templater_Node_Macro $node)
    {
        $this->_macros[$name] = $node;

        return $this;
    }

    /**
     * Add trait
     *
     * @param mixed $trait
     *
     * @return Apishka_Templater_Parser
     */

    public function addTrait($trait)
    {
        $this->_traits[] = $trait;

        return $this;
    }

    /**
     * Has traits
     *
     * @return bool
     */

    public function hasTraits()
    {
        return count($this->_traits) > 0;
    }

    /**
     * Embed template
     *
     * @param Apishka_Templater_Node_Module $template
     *
     * @return Apishka_Templater_Parser
     */

    public function embedTemplate(Apishka_Templater_Node_Module $template)
    {
        $template->setIndex(mt_rand());

        $this->_embeddedTemplates[] = $template;

        return $this;
    }

    /**
     * Add imported symbol
     *
     * @param string                            $type
     * @param string                            $alias
     * @param string                            $name
     * @param Apishka_Templater_Node_Expression $node
     *
     * @return Apishka_Templater_Parser
     */

    public function addImportedSymbol($type, $alias, $name = null, Apishka_Templater_Node_Expression $node = null)
    {
        $this->_importedSymbols[0][$type][$alias] = array('name' => $name, 'node' => $node);

        return $this;
    }

    /**
     * Get imported symbol
     *
     * @param string $type
     * @param string $alias
     *
     * @return mixed
     */

    public function getImportedSymbol($type, $alias)
    {
        foreach ($this->_importedSymbols as $functions)
        {
            if (isset($functions[$type][$alias]))
                return $functions[$type][$alias];
        }
    }

    /**
     * Is main scope
     *
     * @return bool
     */

    public function isMainScope()
    {
        return 1 === count($this->_importedSymbols);
    }

    /**
     * Push local scope
     *
     * @return Apishka_Templater_Parser
     */

    public function pushLocalScope()
    {
        array_unshift($this->_importedSymbols, array());

        return $this;
    }

    /**
     * Pop local scope
     *
     * @return Apishka_Templater_Parser
     */

    public function popLocalScope()
    {
        array_shift($this->_importedSymbols);

        return $this;
    }

    /**
     * Gets the expression parser.
     *
     * @return Apishka_Templater_ExpressionParser The expression parser
     */

    public function getExpressionParser()
    {
        return $this->_expressionParser;
    }

    /**
     * Get parent
     *
     * @return mixed
     */

    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Set parent
     *
     * @param mixed $parent
     *
     * @return jclass
     */

    public function setParent($parent)
    {
        $this->_parent = $parent;

        return $this;
    }

    /**
     * Gets the token stream.
     *
     * @return Apishka_Templater_TokenStream The token stream
     */

    public function getStream()
    {
        return $this->_stream;
    }

    /**
     * Gets the current token.
     *
     * @return Apishka_Templater_Token The current token
     */

    public function getCurrentToken()
    {
        return $this->_stream->getCurrent();
    }

    /**
     * Filter body nodes
     *
     * @param Apishka_Templater_Node $node
     *
     * @return Apishka_Templater_Node
     */

    private function filterBodyNodes(Apishka_Templater_Node $node)
    {
        // check that the body does not contain non-empty output nodes
        if (
            ($node instanceof Apishka_Templater_Node_Text && !ctype_space($node->getAttribute('data')))
            ||
            (!$node instanceof Apishka_Templater_Node_Text && !$node instanceof Apishka_Templater_Node_BlockReference && $node instanceof Apishka_Templater_NodeOutputInterface)
        )
        {
            if (false !== strpos((string) $node, chr(0xEF) . chr(0xBB) . chr(0xBF)))
                throw new Apishka_Templater_Exception_Syntax('A template that extends another one cannot have a body but a byte order mark (BOM) has been detected; it must be removed.', $node->getLine(), $this->getFilename());

            throw new Apishka_Templater_Exception_Syntax('A template that extends another one cannot have a body.', $node->getLine(), $this->getFilename());
        }

        // bypass "set" nodes as they "capture" the output
        if ($node instanceof Apishka_Templater_Node_Set)
            return $node;

        if ($node instanceof Apishka_Templater_NodeOutputInterface)
            return;

        foreach ($node as $k => $n)
        {
            if (null !== $n && null === $this->filterBodyNodes($n))
                $node->removeNode($k);
        }

        return $node;
    }
}
