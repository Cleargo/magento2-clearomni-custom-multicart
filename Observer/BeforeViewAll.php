<?php
/**
 * Webkul Software
 *
 * @category  Webkul
 * @package   Webkul_Mpsplitcart
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Cleargo\MultiCart\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;


class BeforeViewAll implements ObserverInterface
{

    protected $messageManager;
    /**
     * @var \Cleargo\MultiCart\Helper\Data
     */
    protected $helper;

    public function __construct(
        ManagerInterface $messageManager,
        \Cleargo\MultiCart\Helper\Data $helper
    )
    {
        $this->messageManager = $messageManager;
        $this->helper     = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //this observer for cache enabled
        $actionName = $observer->getEvent()->getRequest()->getFullActionName();
        $requestURI = $_SERVER['REQUEST_URI'];
//        if(in_array($requestURI,['/index','/checkout/cart','/checkout/cart/'])){
//            $this->helper->setFirstQuoteAsQuote();
//        }
    }
}
