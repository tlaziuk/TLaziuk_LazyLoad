<?php
class TLaziuk_LazyLoad_Helper_Data
    extends TLaziuk_LazyLoad_Helper_Abstract
{
    const CACHE_GROUP = Mage_Core_Block_Abstract::CACHE_GROUP;
    const CACHE_KEY_PREFIX = self::CACHE_TAG;
    const CACHE_LIFETIME = Mage_Core_Model_Cache::DEFAULT_LIFETIME;
    const CACHE_TAG = 'TLaziuk_LazyLoad';

    public function loadCache($key) {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            return Mage::app()->loadCache($key);
        }
        return false;
    }

    public function saveCache($data, $key, array $tags = array(self::CACHE_TAG), $lifetime = self::CACHE_LIFETIME) {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            Mage::app()->saveCache($data, $key, $tags, $lifetime);
        }
        return $this;
    }

    /**
     * check config conditions if block is lazy
     *
     * @param Mage_Core_Block_Abstract $block
     *
     * @return boolean
     */
    public function isBlockLazy(Mage_Core_Block_Abstract $block)
    {
        $blk = $this->getBlock();
        return in_array($block->getNameInLayout(), $blk[TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_INCLUDE])
            || (in_array($block->getModuleName(), $this->getModule()) && !in_array($block->getNameInLayout(), $blk[TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_EXCLUDE]));
    }

    /**
     * @param string $html
     * @param boolean $skipScript (optional)
     *
     * @return string
     */
    public function lazyHtml($html, $skipScript = true) {
        Varien_Profiler::start('TLaziuk_LazyLoad::lazyHtml');
        try {
            $tmp = array();
            if ($skipScript) {
                $html = preg_replace_callback('|<(script)([^>]*?)>([\S\s]+?)<\/\1>|i', function ($matches) use (&$tmp) {
                    $hash = md5($matches[3]);
                    $tmp[$hash] = $matches[0];
                    return "<!--script:{$hash}-->";
                }, $html);
            }
            $html = preg_replace_callback($this->getPattern(), $this->getCallback(), $html);
            if ($skipScript) {
                $html = preg_replace_callback('|<!--script:(.*?)-->|i', function ($matches) use (&$tmp) {
                    if (isset($tmp[$matches[1]])) {
                        return $tmp[$matches[1]];
                    }
                    return $matches[0];
                }, $html);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        Varien_Profiler::stop('TLaziuk_LazyLoad::lazyHtml');
        return $html;
    }

    protected $_commentPattern = '|<!--(.*?)-->([\s\S]*?)<!--\1-->|i';
    protected $_tmpHtml = array();

    /**
     * @param Mage_Core_Block_Abstract $block
     * @param Varien_Object $transport
     *
     * @return Mage_Core_Block_Abstract
     */
    public function lazyBlock(Mage_Core_Block_Abstract $block, Varien_Object $transport) {
        try {
            $name = $block->getModuleName() . '::' . get_class($block) . '::' . ($block->getNameInLayout() ? $block->getNameInLayout() : 'anonymous');
            Varien_Profiler::start('TLaziuk_LazyLoad::lazyBlock::' . $name);
            $key = self::CACHE_KEY_PREFIX . '::' . $name;
            if (!$block->getCacheLifetime() || !($html = $this->loadCache($key))) {
                $html = $transport->getHtml();
                $html = preg_replace_callback($this->_commentPattern, function($matches) {
                    if (!empty($matches[2])) {
                        $this->_tmpHtml[$matches[1]] = $matches[2];
                        return '<!--' . $matches[1] . '-->' . '<!--' . $matches[1] . '-->';
                    }
                    return '';
                }, $html);
                if ($this->isBlockLazy($block)) {
                    $html = $this->lazyHtml($html);
                }
                $html = preg_replace_callback($this->_commentPattern, function($matches) {
                    if (!empty($this->_tmpHtml[$matches[1]])) {
                        return $this->_tmpHtml[$matches[1]];
                    }
                    return $matches[2];
                }, $html);
                $this->saveCache($html, $key);
            } else {
                $this->_tmpHtml[$name] = $html;
            }
            if ($block->getParentBlock()) {
                $html = "<!--{$name}-->{$html}<!--{$name}-->";
            }
            $transport->setHtml($html);
            Varien_Profiler::stop('TLaziuk_LazyLoad::lazyBlock::' . $name);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $block;
    }

    /**
     * @param Zend_Controller_Response_Abstract $response
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function lazyResponse(Zend_Controller_Response_Abstract $response) {
        Varien_Profiler::start('TLaziuk_LazyLoad::lazyResponse');
        foreach ($response->getBody(true) as $name => $html) {
            Varien_Profiler::start('TLaziuk_LazyLoad::lazyResponse::'.$name);
            try {
                $lazy = $this->lazyHtml($html);
                $response->setBody($lazy, $name);
            } catch (Exception $e) {
                Mage::logException($e);
            }
            Varien_Profiler::stop('TLaziuk_LazyLoad::lazyResponse::'.$name);
        }
        Varien_Profiler::stop('TLaziuk_LazyLoad::lazyResponse');
        return $response;
    }
}

