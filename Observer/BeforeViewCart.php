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

class BeforeViewCart implements ObserverInterface
{
    protected $helper;

    public function __construct(
        \Cleargo\MultiCart\Helper\Data $helper
    ) {
        $this->helper     = $helper;
    }

    /**
     * [executes on controller_action_predispatch_checkout_index_index event]
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $checkoutSession=$this->helper->getCheckoutSession();
//        var_dump(intval($checkoutSession->getFirstQuoteId()));
//        exit;
        if(intval($checkoutSession->getFirstQuoteId())<=0){
            $checkoutSession->setFirstQuoteId($checkoutSession->getQuote()->getId());
        }
        $this->helper->setFirstQuoteAsQuote();
    }
}
