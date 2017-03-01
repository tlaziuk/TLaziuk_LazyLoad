<?php

class TLaziuk_LazyLoad_Model_System_Config_Source_Type
{
    const VAL_INCLUDE = 'include';
    const VAL_EXCLUDE = 'exclude';

    public function toOptionArray()
    {
        $helper = Mage::helper('tlaziuk_lazyload');

        return array(
            array('value' => self::VAL_INCLUDE, 'label' => $helper->__('Include')),
            array('value' => self::VAL_EXCLUDE, 'label' => $helper->__('Exclude')),
        );
    }
    public function toArray()
    {
        $res = array();

        foreach ($this->toOptionArray() as $opt) {
            $res[$opt['value']] = $opt['label'];
        }

        return $res;
    }
}
