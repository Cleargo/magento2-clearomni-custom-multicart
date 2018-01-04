<?php
namespace Cleargo\MultiCart\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Webapi\Exception;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * [$_coreSession description]
     * @var SessionManager
     */
    protected $_coreSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Cleargo\MultiCart\Helper\Data
     */
    protected $helper;
    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param SessionManager                              $coreSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        SessionManager $coreSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Cleargo\MultiCart\Helper\Data $helper
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->_date = $date;
        $this->helper=$helper;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order=$observer->getOrder();
        if($this->helper->getCheckoutSession()->getFirstQuoteId()>0) {
            if ($this->helper->getCheckoutSession()->getFirstQuoteId() != $order->getQuoteId()) {
                $this->helper->changeMemberQuoteBackToActive($this->helper->getCheckoutSession()->getFirstQuoteId());
            }
        }
        $this->helper->getCheckoutSession()->unsCartToken();
        $this->helper->getCheckoutSession()->unsSecondQuoteId();
        $this->_customerSession->unsRetailerId();
        $this->_customerSession->unsFirstQuoteId();
    }
}
