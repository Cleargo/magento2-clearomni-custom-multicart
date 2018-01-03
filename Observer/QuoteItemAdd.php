<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cleargo\MultiCart\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class QuoteItemAdd implements ObserverInterface
{
    /**
     * @var \Cleargo\MultiCart\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @param CheckSalesRulesAvailability $checkSalesRulesAvailability
     */
    public function __construct(
        \Cleargo\MultiCart\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->helper = $helper;
        $this->request=$request;
    }

    /**
     * After save attribute if it is not used for promo rules already check rules for containing this attribute
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $storeCode=$this->request->getParam('store');
        if($this->helper->getStoreField()){
            if(!empty($storeCode)) {
                $observer->getQuoteItem()->setStoreCode($storeCode);
            }
        }
        
        return $this;
    }
}
