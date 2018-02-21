<?php


namespace Cleargo\MultiCart\Observer\Checkout;

class CartProductAddAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $objectManager;


    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customRepos;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $datetime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Cleargo\MultiCart\Helper\Data 
     */
    protected $helper;

    protected $request;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    protected $quoteRepository;

    protected $customerSession;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customRepos,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Cleargo\MultiCart\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->objectManager = $objectManager;
        $this->customRepos = $customRepos;
        $this->datetime = $dateTime;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->helper=$helper;
        $this->request=$request;
        $this->productRepository=$productRepository;
        $this->quoteRepository=$quoteRepository;
        $connection=$objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $connection->getConnection();
        $this->connection=$connection;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
//        var_dump('run',$this->request->getParams());
//        exit;
        $product = $observer->getProduct();
        $quoteItem = $observer->getQuoteItem();
        $this->checkoutSession->setFirstQuoteId($quoteItem->getQuoteId());
        $quote=$this->checkoutSession->getQuote();
        //Your observer code
        $this->checkoutSession->unsToken();
        $this->helper->createCart();
        $this->helper->getCart();
        if(intval($this->request->getParam('secondcart'))==1) {
            if (in_array($quoteItem->getProductType(), ['configurable', 'bundle'])) {
                $parentProduct = $quoteItem->getProduct();
                $result = $this->helper->addProductToCart($product, $quoteItem->getQtyToAdd(), $parentProduct, $this->request->getParam('super_attribute'));
            } else {
                $result = $this->helper->addProductToCart($product, $quoteItem->getQtyToAdd());
            }
            if($quoteItem->getQty()-$quoteItem->getQtyToAdd()==0) {
                $quote->deleteItem($quoteItem);
            }else{
                $quoteItem->setQty($quoteItem->getQty()-$quoteItem->getQtyToAdd());
            }
            if($this->helper->getStoreField()) {
                $storeCode=$this->request->getParam('store');
                $result = json_decode($result,true);
                if (isset($result['item_id'])&&!empty($storeCode)){
                    //no idea why using quoteitem model not working....
                    $query=$this->connection->prepare('update quote_item set store_code=? where item_id=?');
                    $query->bindValue(1,$storeCode);
                    $query->bindValue(2,$result['item_id']);
                    $query->execute();
                    $this->customerSession->setRetailerId($storeCode);
                    $query=$this->connection->prepare('update quote set seller_id=? where entity_id=?');
                    $query->bindValue(1,$storeCode);
                    $query->bindValue(2,$result['quote_id']);
                    $query->execute();
                }
            }
//            $this->quoteRepository->save($quote);
        }
    }
}
