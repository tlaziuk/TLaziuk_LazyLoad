<?php
class TLaziuk_LazyLoad_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED = 'advanced/tlaziuk_lazyload/enabled';
    const XML_PATH_MODULE = 'advanced/tlaziuk_lazyload/module';
    const XML_PATH_ALL = 'advanced/tlaziuk_lazyload/all';
    const XML_PATH_ATTRIBUTE = 'advanced/tlaziuk_lazyload/attribute';

    const CACHE_TAG = 'TLaziuk_LazyLoad';
    const CACHE_GROUP = Mage_Core_Block_Abstract::CACHE_GROUP;
    const CACHE_LIFETIME = Mage_Core_Model_Cache::DEFAULT_LIFETIME;
    const CACHE_KEY_PREFIX = self::CACHE_TAG;

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

    protected $_attribute;

    public function getAttribute() {
        if (empty($this->_attribute)) {
            $this->_attribute = Mage::getStoreConfig(self::XML_PATH_ATTRIBUTE);
        }
        return $this->_attribute;
    }

    protected $_placeholder;

    public function getPlaceholder() {
        if (empty($this->_placeholder)) {
            $this->setPlaceholder(Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl() . '/placeholder/' . Mage::getStoreConfig("catalog/placeholder/small_image_placeholder"));
        }
        return $this->_placeholder;
    }

    public function setPlaceholder($value) {
        $this->_placeholder = (string) $value;
        return $this;
    }

    protected $_pattern;

    public function getPattern() {
        if (empty($this->_pattern)) {
            $this->setPattern('|<img([^>]*?)src=([\'\"])(.*?)\2([^>]*?)>|i');
        }
        return $this->_pattern;
    }

    public function setPattern($pattern) {
        $this->_pattern = $pattern;
        return $this;
    }

    protected $_callback;

    public function getCallback() {
        if (empty($this->_callback)) {
            $this->setCallback(function ($matches) {
                if (strpos($matches[0], $this->getAttribute()) === false) {
                    return "<img{$matches[1]} {$this->getAttribute()}={$matches[2]}{$matches[3]}{$matches[2]} src={$matches[2]}{$this->getPlaceholder()}{$matches[2]} {$matches[4]}>";
                }
                return $matches[0];
            });
        }
        return $this->_callback;
    }

    public function setCallback(callable $callback) {
        $this->_callback = $callback;
        return $this;
    }

    public function isEnabled() {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED);
    }

    public function isAll() {
        return Mage::getStoreConfigFlag(self::XML_PATH_ALL);
    }

    public function getModule() {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_MODULE));
    }

    public function lazyHtml($html) {
        Varien_Profiler::start('TLaziuk_LazyLoad::lazyHtml');
        try {
            $html = preg_replace_callback($this->getPattern(), $this->getCallback(), $html);
        } catch (Exception $e) {
            Mage::logException($e);
        }
        Varien_Profiler::stop('TLaziuk_LazyLoad::lazyHtml');
        return $html;
    }

    protected $_commentPattern = '|<!--(.*?)-->([\s\S]*?)<!--\1-->|i';
    protected $_tmpHtml = array();

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
                if (in_array($block->getModuleName(), $this->getModule())) {
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

