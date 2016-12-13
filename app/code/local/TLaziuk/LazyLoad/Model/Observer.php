<?php
class TLaziuk_LazyLoad_Model_Observer
{
    public function controllerFrontSendResponseBefore(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('tlaziuk_lazyload');
        if ($helper->isEnabled() && $helper->isAll()) {

            /* @var Mage_Core_Controller_Varien_Front */
            $front = $observer->getEvent()->getFront();

            $helper->lazyResponse($front->getResponse());
        }
    }

    public function coreBlockAbstractToHtmlAfter(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('tlaziuk_lazyload');
        if ($helper->isEnabled() && !$helper->isAll()) {

            /* @var Varien_Event */
            $event = $observer->getEvent();

            $helper->lazyBlock($event->getBlock(), $event->getTransport());
        }
    }
}

