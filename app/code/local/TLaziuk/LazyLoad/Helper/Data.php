<?php
class TLaziuk_LazyLoad_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED = 'advanced/tlaziuk_lazyload/enabled';
    const XML_PATH_MODULE = 'advanced/tlaziuk_lazyload/module';
    const XML_PATH_ALL = 'advanced/tlaziuk_lazyload/all';
    const XML_PATH_ATTRIBUTE = 'advanced/tlaziuk_lazyload/attribute';

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

    public function lazyBlock(Mage_Core_Block_Abstract $block, Varien_Object $transport) {
        if (in_array($block->getModuleName(), $this->getModule())) {
            Varien_Profiler::start('TLaziuk_LazyLoad::lazyBlock::'.$block->getModuleName());
            try {
                $transport->setHtml($this->lazyHtml($transport->getHtml()));
            } catch (Exception $e) {
                Mage::logException($e);
            }
            Varien_Profiler::stop('TLaziuk_LazyLoad::lazyBlock::'.$block->getModuleName());
        }
        return $block;
    }

    public function lazyResponse(Zend_Controller_Response_Abstract $response) {
        Varien_Profiler::start('TLaziuk_LazyLoad::lazyResponse');
        foreach ($response->getBody(true) as $name => $html) {
            Varien_Profiler::start('TLaziuk_LazyLoad::lazyResponse::'.$name);
            try {
                $response->setBody($this->lazyHtml($html), $name);
            } catch (Exception $e) {
                Mage::logException($e);
            }
            Varien_Profiler::stop('TLaziuk_LazyLoad::lazyResponse::'.$name);
        }
        Varien_Profiler::stop('TLaziuk_LazyLoad::lazyResponse');
        return $response;
    }
}

