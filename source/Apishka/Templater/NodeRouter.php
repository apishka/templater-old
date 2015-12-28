<?php

/**
 * Apishka templater node router
 *
 * @uses \Apishka\EasyExtend\Router\ByKeyAbstract
 *
 * @author Alexander "grevus" Lobtsov <alex@lobtsov.com>
 */

class Apishka_Templater_NodeRouter extends \Apishka\EasyExtend\Router\ByKeyAbstract
{
    /**
     * Get items by type
     *
     * @param string $type
     *
     * @return array
     */

    public function getItemsByType($type)
    {
        $items = $this->getData();

        $result = array();
        foreach ($items as $key => $data)
        {
            if (array_key_exists($type, $data['types']))
                $result[$key] = $data;
        }

        return $result;
    }

    /**
     * Checks item for correct information
     *
     * @param \ReflectionClass $reflector
     *
     * @return bool
     */

    protected function isCorrectItem(\ReflectionClass $reflector)
    {
        return $reflector->isSubclassOf('Apishka_Templater_NodeInterface');
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

        $data['types'] = $item->getSupportedTypes();

        return $data;
    }
}
