<?php

/**
 * Apishka templater node expression unary router
 *
 * @uses \Apishka\EasyExtend\Router\ByKeyAbstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_Node_Expression_UnaryRouter extends \Apishka\EasyExtend\Router\ByKeyAbstract
{
    /**
     * Checks item for correct information
     *
     * @param \ReflectionClass $reflector
     *
     * @return bool
     */

    protected function isCorrectItem(\ReflectionClass $reflector)
    {
        return $reflector->isSubclassOf('Apishka_Templater_Node_Expression_UnaryAbstract');
    }

    /**
     * Get class variants
     *
     * @param \ReflectionClass $reflector
     * @param object           $item
     *
     * @return array
     */

    protected function getClassVariants(\ReflectionClass $reflector, $item)
    {
        return $item->getSupportedNames();
    }

    /**
     * Get class data
     *
     * @param \ReflectionClass $reflector
     * @param mixed            $item
     *
     * @return array
     */

    protected function getClassData(\ReflectionClass $reflector, $item)
    {
        $data = parent::getClassData($reflector, $item);

        $data['precedence']     = $item->getPrecedence();

        return $data;
    }
}
