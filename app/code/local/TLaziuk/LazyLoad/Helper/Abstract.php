<?php
abstract class TLaziuk_LazyLoad_Helper_Abstract
    extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ALL = 'advanced/tlaziuk_lazyload/all';
    const XML_PATH_ATTRIBUTE = 'advanced/tlaziuk_lazyload/attribute';
    const XML_PATH_BLOCK = 'advanced/tlaziuk_lazyload/block';
    const XML_PATH_ENABLED = 'advanced/tlaziuk_lazyload/enabled';
    const XML_PATH_MODULE = 'advanced/tlaziuk_lazyload/module';
    const XML_PATH_PATTERN = 'advanced/tlaziuk_lazyload/pattern';
    const XML_PATH_PLACEHOLDER = 'advanced/tlaziuk_lazyload/placeholder';

    /**
     * @return string
     */
    public function getAttribute()
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_ATTRIBUTE));
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_PLACEHOLDER));
    }

    /** @var string */
    protected $_pattern = null;

    /**
     * @return string
     */
    public function getPattern()
    {
        if (!is_string($this->_pattern)) {
            $pattern = trim(Mage::getStoreConfig(self::XML_PATH_PATTERN));
            if (!$this->_isPatternValid($pattern)) {
                $pattern = "|<img([^>]*?)src=([\'\"])(.*?)\2([^>]*?)>|i";
            }
            $this->_pattern = $pattern;
        }
        return $this->_pattern;
    }

    /**
     * @param string $pattern
     *
     * @return self
     */
    public function setPattern($pattern)
    {
        if ($this->_isPatternValid($pattern)) {
            $this->_pattern = $pattern;
        } else {
            throw new Exception("pattern '{$pattern}' not valid");
        }
        return $this;
    }

    /**
     * @param mixed $pattern
     *
     * @return bool
     */
    protected function _isPatternValid($pattern)
    {
        return !(empty($pattern) && preg_match($pattern, "") === false);
    }

    /** @var callable */
    protected $_callback = null;

    /**
     * @return callable
     */
    public function getCallback()
    {
        if (!is_callable($this->_callback)) {
            $this->_callback = function ($matches) {
                if (strpos($matches[0], $this->getAttribute()) === false) {
                    return "<img{$matches[1]}{$this->getAttribute()}={$matches[2]}{$matches[3]}{$matches[2]} src={$matches[2]}{$this->getPlaceholder()}{$matches[2]}{$matches[4]}>";
                }
                return $matches[0];
            };
        }
        return $this->_callback;
    }

    /**
     * @param callable $callback
     *
     * @return self
     */
    public function setCallback(callable $callback)
    {
        $this->_callback = $callback;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED);
    }

    /**
     * @return boolean
     */
    public function isAll()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ALL);
    }

    /**
     * @return string[]
     */
    public function getModule()
    {
        return explode(',', Mage::getStoreConfig(self::XML_PATH_MODULE));
    }

    /**
     * @return array
     */
    public function getBlock()
    {
        $res = array(
            TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_EXCLUDE => array(),
            TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_INCLUDE => array(),
        );

        try {
            $block = Mage::getStoreConfig(self::XML_PATH_BLOCK);
            if (!empty($block) && is_array($arr = Zend_Serializer::unserialize($block))) {
                foreach ($arr as $row) {
                    if (isset($row['type'], $row['name'])) {
                        if ($row['type'] == TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_EXCLUDE) {
                            $res[TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_EXCLUDE][] = $row['name'];
                        } elseif ($row['type'] == TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_INCLUDE) {
                            $res[TLaziuk_LazyLoad_Model_System_Config_Source_Type::VAL_INCLUDE][] = $row['name'];
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            Mage::logException($exception);
        }

        return $res;
    }

    /**
     * overwrite default isModuleEnabled method
     *
     * @param string $moduleName (optional) full module name
     *
     * @return boolean
     */
    public function isModuleEnabled($moduleName = null)
    {
        if ($moduleName === $this->_getModuleName()) {
            return $this->isEnabled() && parent::isModuleEnabled($moduleName);
        }
        return parent::isModuleEnabled($moduleName);
    }
}
