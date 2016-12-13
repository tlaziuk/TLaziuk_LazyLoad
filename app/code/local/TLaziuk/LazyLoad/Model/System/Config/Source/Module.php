<?php
class TLaziuk_LazyLoad_Model_System_Config_Source_Module
{

    protected $_options = array();

    public function toOptionArray()
    {
        if (empty($this->_options)) {
            $config = Mage::app()->getConfig();
            $modules = (array)$config->getNode('modules')->asArray();
            ksort($modules);
            foreach ($modules as $name => $data) {
                $this->_options[] = array(
                    'value' => $name,
                    'label' => $name,
                );
            }
        }
        return $this->_options;
    }

    public function toArray()
    {
        $res = array();
        foreach ($this->toOptionArray() as $option) {
            $res[$option['value']] = $option['label'];
        }
        return $res;
    }
}
