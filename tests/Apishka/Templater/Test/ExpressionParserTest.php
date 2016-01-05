<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class Apishka_Templater_Test_ExpressionParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     * @dataProvider getFailingTestsForAssignment
     */

    public function testCanOnlyAssignToNames($template)
    {
        $parser = new Apishka_Templater_Parser();
        $tokenizer = new Apishka_Templater_Lexer();

        $parser->parse($tokenizer->tokenize($template, 'index'));
    }

    /**
     * Get failing tests for assignment
     *
     * @return array
     */

    public function getFailingTestsForAssignment()
    {
        return array(
            array('{% set false = "foo" %}'),
            array('{% set true = "foo" %}'),
            array('{% set none = "foo" %}'),
            array('{% set 3 = "foo" %}'),
            array('{% set 1 + 2 = "foo" %}'),
            array('{% set "bar" = "foo" %}'),
            array('{% set %}{% endset %}'),
        );
    }

    /**
     */

    /**
     * Test array expression
     *
     * @dataProvider getTestsForArray
     *
     * @param mixed $template
     * @param mixed $expected
     */

    public function testArrayExpression($template, $expected)
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $stream     = $tokenizer->tokenize($template, 'index');
        $parser     = new Apishka_Templater_Parser();

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     * @dataProvider getFailingTestsForArray
     */

    public function testArraySyntaxError($template)
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize($template, 'index'));
    }

    /**
     * Get failing tests for array
     *
     * @return array
     */

    public function getFailingTestsForArray()
    {
        return array(
            array('{{ [1, "a": "b"] }}'),
            array('{{ {"a": "b", 2} }}'),
        );
    }

    /**
     * Get tests for array
     *
     * @return array
     */

    public function getTestsForArray()
    {
        return array(
            // simple array
            array('{{ [1, 2] }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant(0, 1),
                  new Apishka_Templater_Node_Expression_Constant(1, 1),

                  new Apishka_Templater_Node_Expression_Constant(1, 1),
                  new Apishka_Templater_Node_Expression_Constant(2, 1),
                ), 1),
            ),

            // array with trailing ,
            array('{{ [1, 2, ] }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant(0, 1),
                  new Apishka_Templater_Node_Expression_Constant(1, 1),

                  new Apishka_Templater_Node_Expression_Constant(1, 1),
                  new Apishka_Templater_Node_Expression_Constant(2, 1),
                ), 1),
            ),

            // simple hash
            array('{{ {"a": "b", "b": "c"} }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant('a', 1),
                  new Apishka_Templater_Node_Expression_Constant('b', 1),

                  new Apishka_Templater_Node_Expression_Constant('b', 1),
                  new Apishka_Templater_Node_Expression_Constant('c', 1),
                ), 1),
            ),

            // hash with trailing ,
            array('{{ {"a": "b", "b": "c", } }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant('a', 1),
                  new Apishka_Templater_Node_Expression_Constant('b', 1),

                  new Apishka_Templater_Node_Expression_Constant('b', 1),
                  new Apishka_Templater_Node_Expression_Constant('c', 1),
                ), 1),
            ),

            // hash in an array
            array('{{ [1, {"a": "b", "b": "c"}] }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant(0, 1),
                  new Apishka_Templater_Node_Expression_Constant(1, 1),

                  new Apishka_Templater_Node_Expression_Constant(1, 1),
                  new Apishka_Templater_Node_Expression_Array(array(
                        new Apishka_Templater_Node_Expression_Constant('a', 1),
                        new Apishka_Templater_Node_Expression_Constant('b', 1),

                        new Apishka_Templater_Node_Expression_Constant('b', 1),
                        new Apishka_Templater_Node_Expression_Constant('c', 1),
                      ), 1),
                ), 1),
            ),

            // array in a hash
            array('{{ {"a": [1, 2], "b": "c"} }}', new Apishka_Templater_Node_Expression_Array(array(
                  new Apishka_Templater_Node_Expression_Constant('a', 1),
                  new Apishka_Templater_Node_Expression_Array(array(
                        new Apishka_Templater_Node_Expression_Constant(0, 1),
                        new Apishka_Templater_Node_Expression_Constant(1, 1),

                        new Apishka_Templater_Node_Expression_Constant(1, 1),
                        new Apishka_Templater_Node_Expression_Constant(2, 1),
                      ), 1),
                  new Apishka_Templater_Node_Expression_Constant('b', 1),
                  new Apishka_Templater_Node_Expression_Constant('c', 1),
                ), 1),
            ),
        );
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     */
    public function testStringExpressionDoesNotConcatenateTwoConsecutiveStrings()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $stream     = $tokenizer->tokenize('{{ "a" "b" }}', 'index');
        $parser     = new Apishka_Templater_Parser();

        $parser->parse($stream);
    }

    /**
     * @dataProvider getTestsForString
     */
    public function testStringExpression($template, $expected)
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $stream     = $tokenizer->tokenize($template, 'index');
        $parser     = new Apishka_Templater_Parser();

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0)->getNode('expr'));
    }

    public function getTestsForString()
    {
        return array(
            array(
                '{{ "foo" }}', new Apishka_Templater_Node_Expression_Constant('foo', 1),
            ),
            array(
                '{{ "foo #{bar}" }}', new Apishka_Templater_Node_Expression_Binary_Concat(
                    new Apishka_Templater_Node_Expression_Constant('foo ', 1),
                    new Apishka_Templater_Node_Expression_Name('bar', 1),
                    1
                ),
            ),
            array(
                '{{ "foo #{bar} baz" }}', new Apishka_Templater_Node_Expression_Binary_Concat(
                    new Apishka_Templater_Node_Expression_Binary_Concat(
                        new Apishka_Templater_Node_Expression_Constant('foo ', 1),
                        new Apishka_Templater_Node_Expression_Name('bar', 1),
                        1
                    ),
                    new Apishka_Templater_Node_Expression_Constant(' baz', 1),
                    1
                ),
            ),

            array(
                '{{ "foo #{"foo #{bar} baz"} baz" }}', new Apishka_Templater_Node_Expression_Binary_Concat(
                    new Apishka_Templater_Node_Expression_Binary_Concat(
                        new Apishka_Templater_Node_Expression_Constant('foo ', 1),
                        new Apishka_Templater_Node_Expression_Binary_Concat(
                            new Apishka_Templater_Node_Expression_Binary_Concat(
                                new Apishka_Templater_Node_Expression_Constant('foo ', 1),
                                new Apishka_Templater_Node_Expression_Name('bar', 1),
                                1
                            ),
                            new Apishka_Templater_Node_Expression_Constant(' baz', 1),
                            1
                        ),
                        1
                    ),
                    new Apishka_Templater_Node_Expression_Constant(' baz', 1),
                    1
                ),
            ),
        );
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     */
    public function testAttributeCallDoesNotSupportNamedArguments()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ foo.bar(name="Foo") }}', 'index'));
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     */
    public function testMacroCallDoesNotSupportNamedArguments()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{% from _self import foo %}{% macro foo() %}{% endmacro %}{{ foo(name="Foo") }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage An argument must be a name. Unexpected token "string" of value "a" ("name" expected) in "index" at line 1
     */
    public function testMacroDefinitionDoesNotSupportNonNameVariableName()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{% macro foo("a") %}{% endmacro %}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage A default value for an argument must be a constant (a boolean, a string, a number, or an array) in "index" at line 1
     * @dataProvider             getMacroDefinitionDoesNotSupportNonConstantDefaultValues
     */
    public function testMacroDefinitionDoesNotSupportNonConstantDefaultValues($template)
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize($template, 'index'));
    }

    public function getMacroDefinitionDoesNotSupportNonConstantDefaultValues()
    {
        return array(
            array('{% macro foo(name = "a #{foo} a") %}{% endmacro %}'),
            array('{% macro foo(name = [["b", "a #{foo} a"]]) %}{% endmacro %}'),
        );
    }

    /**
     * @dataProvider getMacroDefinitionSupportsConstantDefaultValues
     */
    public function testMacroDefinitionSupportsConstantDefaultValues($template)
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize($template, 'index'));
    }

    public function getMacroDefinitionSupportsConstantDefaultValues()
    {
        return array(
            array('{% macro foo(name = "aa") %}{% endmacro %}'),
            array('{% macro foo(name = 12) %}{% endmacro %}'),
            array('{% macro foo(name = true) %}{% endmacro %}'),
            array('{% macro foo(name = ["a"]) %}{% endmacro %}'),
            array('{% macro foo(name = [["a"]]) %}{% endmacro %}'),
            array('{% macro foo(name = {a: "a"}) %}{% endmacro %}'),
            array('{% macro foo(name = {a: {b: "a"}}) %}{% endmacro %}'),
        );
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "cycl" function. Did you mean "cycle" in "index" at line 1?
     */
    public function testUnknownFunction()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ cycl() }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "foobar" function in "index" at line 1.
     */
    public function testUnknownFunctionWithoutSuggestions()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ foobar() }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "lowe" filter. Did you mean "lower" in "index" at line 1?
     */
    public function testUnknownFilter()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ 1|lowe }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "foobar" filter in "index" at line 1.
     */
    public function testUnknownFilterWithoutSuggestions()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ 1|foobar }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "nul" test. Did you mean "null" in "index" at line 1
     */
    public function testUnknownTest()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ 1 is nul }}', 'index'));
    }

    /**
     * @expectedException        Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unknown "foobar" test in "index" at line 1.
     */
    public function testUnknownTestWithoutSuggestions()
    {
        $tokenizer  = new Apishka_Templater_Lexer();
        $parser = new Apishka_Templater_Parser();

        $parser->parse($tokenizer->tokenize('{{ 1 is foobar }}', 'index'));
    }
}
