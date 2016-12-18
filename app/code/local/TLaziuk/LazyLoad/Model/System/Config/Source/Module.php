<?php
class TLaziuk_LazyLoad_Model_System_Config_Source_Module
{

    protected $_options = array();

    public function toOptionArray()
    {
        if (empty($this->_options)) {
            $config = Mage::app()->getConfig();
            $modules = array_keys((array) $config->getNode('modules')->asArray());
            $blocks = array();
            foreach ((array) $config->getNode('global')->xpath('blocks//class') as $block) {
                $blocks = array_merge($blocks, (array) $block);
            }
            foreach ((array) $config->getNode('global')->xpath('blocks//rewrite') as $rewrite) {
                $blocks = array_merge($blocks, array_values($rewrite->asArray()));
            }
            $modules = array_filter($modules, function($value) use (&$blocks) {
                foreach ($blocks as $block) {
                    if (strpos($block, $value) === 0) {
                        return true;
                    }
                }
                return false;
            });
            sort($modules);
            foreach ($modules as $name) {
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
