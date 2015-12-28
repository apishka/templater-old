<?php

/**
 * Apishka templater test lexer test
 *
 * @uses PHPUnit_Framework_TestCase
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Test_LexerTest extends PHPUnit_Framework_TestCase
{
    public function testNameLabelForTag()
    {
        $template = '{% § %}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);

        $stream->expect(Apishka_Templater_Token::TYPE_BLOCK_START);
        $this->assertSame('§', $stream->expect(Apishka_Templater_Token::TYPE_NAME)->getValue());
    }

    public function testNameLabelForFunction()
    {
        $template = '{{ §() }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);

        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $this->assertSame('§', $stream->expect(Apishka_Templater_Token::TYPE_NAME)->getValue());
    }

    public function testBracketsNesting()
    {
        $template = '{{ {"a":{"b":"c"}} }}';

        $this->assertEquals(2, $this->countToken($template, Apishka_Templater_Token::TYPE_PUNCTUATION, '{'));
        $this->assertEquals(2, $this->countToken($template, Apishka_Templater_Token::TYPE_PUNCTUATION, '}'));
    }

    protected function countToken($template, $type, $value = null)
    {
        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);

        $count = 0;
        while (!$stream->isEOF()) {
            $token = $stream->next();
            if ($type === $token->getType()) {
                if (null === $value || $value === $token->getValue()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function testLineDirective()
    {
        $template = "foo\n"
            . "bar\n"
            . "{% line 10 %}\n"
            . "{{\n"
            . "baz\n"
            . "}}\n";

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);

        // foo\nbar\n
        $this->assertSame(1, $stream->expect(Apishka_Templater_Token::TYPE_TEXT)->getLine());
        // \n (after {% line %})
        $this->assertSame(10, $stream->expect(Apishka_Templater_Token::TYPE_TEXT)->getLine());
        // {{
        $this->assertSame(11, $stream->expect(Apishka_Templater_Token::TYPE_VAR_START)->getLine());
        // baz
        $this->assertSame(12, $stream->expect(Apishka_Templater_Token::TYPE_NAME)->getLine());
    }

    public function testLineDirectiveInline()
    {
        $template = "foo\n"
            . "bar{% line 10 %}{{\n"
            . "baz\n"
            . "}}\n";

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);

        // foo\nbar
        $this->assertSame(1, $stream->expect(Apishka_Templater_Token::TYPE_TEXT)->getLine());
        // {{
        $this->assertSame(10, $stream->expect(Apishka_Templater_Token::TYPE_VAR_START)->getLine());
        // baz
        $this->assertSame(11, $stream->expect(Apishka_Templater_Token::TYPE_NAME)->getLine());
    }

    public function testLongComments()
    {
        $template = '{# ' . str_repeat('*', 100000) . ' #}';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);

        // should not throw an exception
    }

    public function testLongVerbatim()
    {
        $template = '{% verbatim %}' . str_repeat('*', 100000) . '{% endverbatim %}';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);

        // should not throw an exception
    }

    public function testLongVar()
    {
        $template = '{{ ' . str_repeat('x', 100000) . ' }}';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);

        // should not throw an exception
    }

    public function testLongBlock()
    {
        $template = '{% ' . str_repeat('x', 100000) . ' %}';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);

        // should not throw an exception
    }

    public function testBigNumbers()
    {
        $template = '{{ 922337203685477580700 }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->next();
        $node = $stream->next();
        $this->assertEquals('922337203685477580700', $node->getValue());
    }

    public function testStringWithEscapedDelimiter()
    {
        $tests = array(
            "{{ 'foo \' bar' }}" => 'foo \' bar',
            '{{ "foo \" bar" }}' => 'foo " bar',
        );

        $lexer = new Apishka_Templater_Lexer();
        foreach ($tests as $template => $expected) {
            $stream = $lexer->tokenize($template);
            $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
            $stream->expect(Apishka_Templater_Token::TYPE_STRING, $expected);
        }
    }

    public function testStringWithInterpolation()
    {
        $template = 'foo {{ "bar #{ baz + 1 }" }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_TEXT, 'foo ');
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'bar ');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
        $stream->expect(Apishka_Templater_Token::TYPE_NAME, 'baz');
        $stream->expect(Apishka_Templater_Token::TYPE_OPERATOR, '+');
        $stream->expect(Apishka_Templater_Token::TYPE_NUMBER, '1');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_END);
    }

    public function testStringWithEscapedInterpolation()
    {
        $template = '{{ "bar \#{baz+1}" }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'bar #{baz+1}');
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_END);
    }

    public function testStringWithHash()
    {
        $template = '{{ "bar # baz" }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'bar # baz');
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_END);
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unclosed """
     */
    public function testStringWithUnterminatedInterpolation()
    {
        $template = '{{ "bar #{x" }}';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);
    }

    public function testStringWithNestedInterpolations()
    {
        $template = '{{ "bar #{ "foo#{bar}" }" }}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'bar ');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'foo');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
        $stream->expect(Apishka_Templater_Token::TYPE_NAME, 'bar');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_END);
    }

    public function testStringWithNestedInterpolationsInBlock()
    {
        $template = '{% foo "bar #{ "foo#{bar}" }" %}';

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_BLOCK_START);
        $stream->expect(Apishka_Templater_Token::TYPE_NAME, 'foo');
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'bar ');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
        $stream->expect(Apishka_Templater_Token::TYPE_STRING, 'foo');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_START);
        $stream->expect(Apishka_Templater_Token::TYPE_NAME, 'bar');
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
        $stream->expect(Apishka_Templater_Token::TYPE_INTERPOLATION_END);
        $stream->expect(Apishka_Templater_Token::TYPE_BLOCK_END);
    }

    public function testOperatorEndingWithALetterAtTheEndOfALine()
    {
        $template = "{{ 1 and\n0}}";

        $lexer = new Apishka_Templater_Lexer();
        $stream = $lexer->tokenize($template);
        $stream->expect(Apishka_Templater_Token::TYPE_VAR_START);
        $stream->expect(Apishka_Templater_Token::TYPE_NUMBER, 1);
        $stream->expect(Apishka_Templater_Token::TYPE_OPERATOR, 'and');
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unclosed "variable" at line 3
     */
    public function testUnterminatedVariable()
    {
        $template = '

{{

bar


';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);
    }

    /**
     * @expectedException Apishka_Templater_Exception_Syntax
     * @expectedExceptionMessage Unclosed "block" at line 3
     */
    public function testUnterminatedBlock()
    {
        $template = '

{%

bar


';

        $lexer = new Apishka_Templater_Lexer();
        $lexer->tokenize($template);
    }
}
