<?php


namespace Cleargo\MultiCart\Controller\Checkout;

class Remove extends \Magento\Framework\App\Action\Action
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
            $customer=$this->_objectManager->create('Magento\Customer\Model\Customer')->load($context->getValue(\Cleargo\MultiCart\Model\Customer\Context::CONTEXT_CUSTOMER_ID));
        }
        if($customer->getId()) {
            $this->helper->deleteItem($this->getRequest()->getParam('item'));
        }
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/cart/index');
        return $resultRedirect;
    }
}