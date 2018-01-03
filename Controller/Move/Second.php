<?php


namespace Cleargo\MultiCart\Controller\Move;

class Second extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
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
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper=$helper;
        $this->checkoutSession=$checkoutSession;
        $this->customerSession=$customerSession;
        $this->cart = $cart;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $cartItem=$this->getRequest()->getParam('item');
        $quote=$this->getRequest()->getParam('quote');
        if((isset($cartItem)&&!empty($cartItem))&&(isset($quote)&&!empty($quote))){
            $this->helper->moveItemToQuote($cartItem,$quote);
        }
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
    }
}