<?php

/**
 * Apishka templater exception
 *
 * @uses Exception
 *
 * @author Evgeny Reykh <evgeny@reykh.com>
 */

class Apishka_Templater_Exception extends Exception
{
    /**
     * Lineno
     *
     * @var int
     */

    private $_lineno;

    /**
     * Filename
     *
     * @var string
     */

    private $_filename;

    /**
     * Raw message
     *
     * @var string
     */

    private $_raw_message;

    /**
     * Constructor.
     *
     * Set both the line number and the filename to false to
     * disable automatic guessing of the original template name
     * and line number.
     *
     * Set the line number to -1 to enable its automatic guessing.
     * Set the filename to null to enable its automatic guessing.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string    $message  The error message
     * @param int       $lineno   The template line where the error occurred
     * @param string    $filename The template file name where the error occurred
     * @param Exception $previous The previous exception
     */

    public function __construct($message, $lineno = -1, $filename = null, Exception $previous = null)
    {
        parent::__construct('', 0, $previous);

        $this->_lineno = $lineno;
        $this->_filename = $filename;

        if (-1 === $this->_lineno || null === $this->_filename)
            $this->guessTemplateInfo();

        $this->_raw_message = $message;

        $this->updateRepr();
    }

    /**
     * Gets the raw message.
     *
     * @return string The raw message
     */
    public function getRawMessage()
    {
        return $this->_raw_message;
    }

    /**
     * Gets the filename where the error occurred.
     *
     * @return string The filename
     */
    public function getTemplateFile()
    {
        return $this->_filename;
    }

    /**
     * Sets the filename where the error occurred.
     *
     * @param string $filename The filename
     */
    public function setTemplateFile($filename)
    {
        $this->_filename = $filename;
        $this->updateRepr();
    }

    /**
     * Gets the template line where the error occurred.
     *
     * @return int The template line
     */

    public function getTemplateLine()
    {
        return $this->_lineno;
    }

    /**
     * Sets the template line where the error occurred.
     *
     * @param int $lineno The template line
     */

    public function setTemplateLine($lineno)
    {
        $this->_lineno = $lineno;

        $this->updateRepr();
    }

    /**
     * Guess
     */

    public function guess()
    {
        $this->guessTemplateInfo();
        $this->updateRepr();
    }

    /**
     * Append message
     *
     * @param string $raw_message
     */

    public function appendMessage($raw_message)
    {
        $this->_raw_message .= $raw_message;
        $this->updateRepr();
    }

    /**
     * Update repr
     */

    private function updateRepr()
    {
        $this->message = $this->_raw_message;

        $dot = false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = true;
        }

        $questionMark = false;
        if ('?' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $questionMark = true;
        }

        if ($this->_filename)
        {
            if (is_string($this->_filename) || (is_object($this->_filename) && method_exists($this->_filename, '__toString')))
            {
                $filename = sprintf('"%s"', $this->_filename);
            }
            else
            {
                $filename = json_encode($this->_filename);
            }

            $this->message .= sprintf(' in %s', $filename);
        }

        if ($this->_lineno && $this->_lineno >= 0)
            $this->message .= sprintf(' at line %d', $this->_lineno);

        if ($dot)
            $this->message .= '.';

        if ($questionMark)
            $this->message .= '?';
    }

    /**
     * Guess template info
     */

    private function guessTemplateInfo()
    {
        $template = null;
        $templateClass = null;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace)
        {
            if (isset($trace['object']) && $trace['object'] instanceof Apishka_Templater_Template && 'Apishka_Templater_Template' !== get_class($trace['object']))
            {
                $currentClass = get_class($trace['object']);
                $isEmbedContainer = 0 === strpos($templateClass, $currentClass);

                if (null === $this->_filename || ($this->_filename == $trace['object']->getTemplateName() && !$isEmbedContainer))
                {
                    $template = $trace['object'];
                    $templateClass = get_class($trace['object']);
                }
            }
        }

        // update template filename
        if (null !== $template && null === $this->_filename)
            $this->_filename = $template->getTemplateName();

        if (null === $template || $this->_lineno > -1)
            return;

        $r = new ReflectionObject($template);
        $file = $r->getFileName();

        // hhvm has a bug where eval'ed files comes out as the current directory
        if (is_dir($file)) {
            $file = '';
        }

        $exceptions = array($e = $this);
        while ($e = $e->getPrevious())
        {
            $exceptions[] = $e;
        }

        while ($e = array_pop($exceptions))
        {
            $traces = $e->getTrace();
            array_unshift($traces, array('file' => $e->getFile(), 'line' => $e->getLine()));

            while ($trace = array_shift($traces))
            {
                if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file'])
                    continue;

                foreach ($template->getDebugInfo() as $codeLine => $templateLine)
                {
                    if ($codeLine <= $trace['line'])
                    {
                        // update template line
                        $this->_lineno = $templateLine;

                        return;
                    }
                }
            }
        }
    }
}
