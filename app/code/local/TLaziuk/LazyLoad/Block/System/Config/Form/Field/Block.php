<?php

class TLaziuk_LazyLoad_Block_System_Config_Form_Field_Block
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected function _prepareToRender()
    {
        $this->addColumn('name', array(
            'label' => $this->__('Name in Layout'),
        ));

        $this->addColumn('type', array(
            'label' => $this->__('Type'),
            'renderer' => $this->_getTypeRenderer(),
        ));

        return parent::_prepareToRender();
    }

    protected $_typeRenderer = null;

    protected function _getTypeRenderer()
    {
        if (is_null($this->_typeRenderer)) {
            $this->_typeRenderer = $this->getLayout()->createBlock(
                'tlaziuk_lazyload/system_config_form_field_type',
                '',
                array(
                    'is_render_to_js_template' => true,
                )
            );
        }
        return $this->_typeRenderer;
    }

    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getTypeRenderer()->calcOptionHash($row->getData('type')),
            'selected="selected"'
        );
    }
}
