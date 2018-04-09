<?php


namespace Cleargo\MultiCart\Controller\Checkout;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    /**
     * @var \Cleargo\MultiCart\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cleargo\MultiCart\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper=$helper;
        $this->checkoutSession=$checkoutSession;
        $this->customerSession=$customerSession;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $context = $this->_objectManager->get('Magento\Framework\App\Http\Context');
        $customer=$this->customerSession->getCustomer();
        if(!$customer->getFirstname()){
            $repos=$this->helper->getCustomerRepos();
            $customer=$this->_objectManager->create('Magento\Customer\Model\Customer')->load($context->getValue(\Cleargo\AigleClearomniConnector\Model\Customer\Context::CONTEXT_CUSTOMER_ID));
        }
        if($customer->getId()){
            $this->helper->turnGuestQuoteToMemberQuote($this->checkoutSession->getSecondQuoteId(),$this->checkoutSession->getQuote()->getId());
            //deactive default quote
            $objectManager=\Magento\Framework\App\ObjectManager::getInstance();
            $connection=$objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $connection->getConnection();
            $query=$connection->prepare('update quote set is_active=0,customer_id=? where entity_id=?');
            $query->bindValue(1,$this->customerSession->getCustomerId());
            $query->bindValue(2,$this->checkoutSession->getQuote()->getId());
            $query->execute();
            //turn guest quote into customer quote
            $query=$connection->prepare('update quote set is_active=1,customer_id=? where entity_id=?');
            $query->bindValue(1,$this->customerSession->getCustomerId());
            $query->bindValue(2,$this->checkoutSession->getSecondQuoteId());
            $query->execute();
        }
        $this->helper->setSecondQuoteAsQuote();
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/index/index');
        return $resultRedirect;
    }
}