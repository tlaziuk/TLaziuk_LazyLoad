<?php

class TLaziuk_LazyLoad_Block_System_Config_Form_Field_Type
    extends Mage_Core_Block_Html_Select
{
    protected function _beforeToHtml()
    {
        $this->setOptions(Mage::getSingleton('tlaziuk_lazyload/system_config_source_type')->toOptionArray());

        return parent::_beforeToHtml();
    }
}
