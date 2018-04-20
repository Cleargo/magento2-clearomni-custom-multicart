<?php


/**
 * Catalog data helper
 */
namespace Cleargo\MultiCart\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_MULTICART_USERNAME_PATH='multicart/multicart/user';
    const XML_MULTICART_PASSWORD_PATH='multicart/multicart/password';
    const XML_MULTICART_URL_PATH='multicart/multicart/url';
    const XML_MULTICART_STORE_FIELD='multicart/multicart/store_field';
    const XML_MULTICART_MUST_MEMBER='multicart/multicart/must_member_api';

    protected $_objectManager;
    protected $_filesystem;


    protected $curl;
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $quoteRepository;
    protected $connection;
    protected $customerSession;
    protected $tokenFactory;
    protected $customerRepos;
    protected $currentCustomer;
    /**
     * @param Magento\Framework\App\Helper\Context $context
     * @param Magento\Framework\ObjectManagerInterface $objectManager
     * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Integration\Model\Oauth\TokenFactory $tokenFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    )
    {
        $this->_objectManager = $objectManager;
        $this->_filesystem = $filesystem;
        $this->curl=$curl;
        $this->scopeConfig = $scopeConfig;
        $this->timezone=$timezone;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->baseUrl=$this->getBaseUrl();
        $this->username=$this->getUserName();
        $this->password=$this->getPassword();
        $this->quoteRepository=$quoteRepository;
        $this->connection=$objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection();
        $this->customerSession=$customerSession;
        $this->tokenFactory=$tokenFactory;
        $this->customerRepos=$customerRepositoryInterface;
        $this->currentCustomer=$currentCustomer;
        parent::__construct($context);
    }


    public function getBaseUrl()
    {
        return $this->scopeConfig->getValue(
            self::XML_MULTICART_URL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getUserName()
    {
        return $this->scopeConfig->getValue(
            self::XML_MULTICART_USERNAME_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getPassword()
    {
        return $this->scopeConfig->getValue(
            self::XML_MULTICART_PASSWORD_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getStoreField()
    {
        return $this->scopeConfig->getValue(
            self::XML_MULTICART_STORE_FIELD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getMemberApi()
    {
        return $this->scopeConfig->getValue(
            self::XML_MULTICART_MUST_MEMBER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Customer\Helper\Session\CurrentCustomer
     */
    public function getCurrentCustomer()
    {
        return $this->currentCustomer;
    }

    /**
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
    public function getCustomerRepos()
    {
        return $this->customerRepos;
    }


    public function authentication(){
        $token=$this->checkoutSession->getToken();
        if(!isset($token)) {
            $result = $this->request('integration/admin/token', 'POST', json_encode(['username' => $this->username, 'password' => $this->password]), true);
            $this->checkoutSession->setToken($result);
        }
    }
    public function createCart(){
        $this->authentication();
//        $this->checkoutSession->unsCartToken();
        $cartToken=$this->checkoutSession->getCartToken();
        if(!isset($cartToken)) {
            $result = $this->request('guest-carts/', 'POST',json_encode([]),true,true);
            $this->checkoutSession->setCartToken($result);
        }
    }
    public function getCheckoutSession(){
        return $this->checkoutSession;
    }
    public function getCart(){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken, 'GET',json_encode([]),true,true);
            $this->checkoutSession->setSecondQuoteId($result['id']);
            return $result;
        }
        return [];
    }
    public function getCartTotal(){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken.'/totals', 'GET',json_encode([]),true,true);
            return $result;
        }
        return [];
    }

    public function applyCoupon($couponCode){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken.'/coupons/'.$couponCode, 'PUT',json_encode([]),true,true);
            return $result;
        }
        return [];
    }
    public function cancelCoupon(){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken.'/coupons', 'DELETE',json_encode([]),true,true);
            return $result;
        }
        return [];
    }

    public function addProductToCart($product,$qty,$parentProduct=false,$superAttribute=[],$productOption=[],$cartToken=false,$mustGuest=true){
        $this->authentication();
        $cartId=$this->checkoutSession->getSecondQuoteId();
        if($cartToken==false) {
            $cartToken = $this->checkoutSession->getCartToken();
        }

        /*
         * {
  "cartItem": {
    "sku": "MH01",
    "qty": 1,
    "quote_id": "4",
    "product_option": {
      "extension_attributes": {
        "configurable_item_options": [
          {
            "option_id": "93",
            "option_value": 52
          },
          {
            "option_id": "141",
            "option_value": 168
          }
        ]
      }
    },
    "extension_attributes": {}
  }
}
        {"store_id":1,"quote_id":"7","product":{},"product_id":"1596","product_type":"configurable","sku":"WS05-XS-Orange","name":"Desiree Fitness Tee","weight":"1.0000","tax_class_id":"2","base_cost":null,"is_qty_decimal":false,"qty_to_add":3,"qty":3,"qty_options":{"1582":{}},"has_children":true}
         */
        
        $order = array(
            'cartItem' => array(
                'quote_id' => (string)$cartToken."",
                'sku' => (string)$product->getSku()."",
                'qty' => (string)$qty.""
            )
        );
        if($parentProduct){
            $data=$parentProduct->getData();
            $order['cartItem']['sku']=$data['sku'];
            foreach ($superAttribute as $key=>$value){
                $order['cartItem']['product_option']['extension_attributes']['configurable_item_options'][]=[
                    'option_id'=>(string)$key,
                    'option_value'=>(string)$value
                ];
            }
        }
        if(!empty($productOption)){
            if(!isset($order['cartItem']['product_option'])){
                $order['cartItem']['product_option']=[];
            }
            foreach ($productOption as $key=>$value){
                $order['cartItem']['product_option']['extension_attributes']['custom_options'][]=[
                    'option_id'=>(string)$key,
                    'option_value'=>(string)$value
                ];
            }
        }
        if($this->getCustomerSession()->isLoggedIn()&&$mustGuest==true){
            $order['cartItem']['quote_id']=$this->getCheckoutSession()->getQuote()->getId();
            $customer=$this->getCustomerRepos()->getById($this->getCustomerSession()->getCustomer()->getId());
//            echo json_encode($order);
//            exit;
            $result = $this->request('carts/mine/items',
                'POST',
                json_encode($order),
                false,
                false,
                $customer->getCustomAttribute('access_token')->getValue()
            );
        }else {
            $result = $this->request('guest-carts/' . $cartToken . '/items',
                'POST',
                json_encode($order),
                false
            );
        }
        return $result;
    }
    public function updateProduct($itemId,$qty){
        $this->authentication();
        $cartId=$this->checkoutSession->getSecondQuoteId();
        $cartToken=$this->checkoutSession->getCartToken();

        $order = array(
            'cartItem' => array(
                'quote_id' => (string)$cartToken."",
                'item_id' => (string)$itemId."",
                'qty' => $qty
            )
        );
        $result=$this->request('guest-carts/' . $cartToken . '/items/'.$itemId,
            'PUT',
            json_encode($order),
            false
        );
        return $result;
    }
    public function request($endpoint, $method = 'GET', $body = FALSE,$json=true,$noToken=false,$customToken='') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $headers = array();
        $headers[] = "Content-Type: application/json";
        $token=$this->checkoutSession->getToken();
        if(isset($token)&&!empty($token)&&$noToken==false){
            if(empty($customToken)) {
                $headers[] = "Authorization: Bearer " . $token;
            }else{
                $headers[] = "Authorization: Bearer " . $customToken;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD,  "cleargo:cleargo1234");
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);
        if($json==true){
            return json_decode($result,true);
        }else {
            return $result;
        }
    }

    public function setFirstQuoteAsQuote(){
        if(intval($this->checkoutSession->getFirstQuoteId())>0) {
            $this->checkoutSession->clearQuote();
            $this->checkoutSession->setQuoteId($this->checkoutSession->getFirstQuoteId());
        }
        return $this->checkoutSession->getQuote();
    }

    public function setSecondQuoteAsQuote(){
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->setQuoteId($this->checkoutSession->getSecondQuoteId());
        return $this->checkoutSession->getQuote();
    }

    public function turnGuestQuoteToMemberQuote($guestQuoteId,$memberQuoteId){
//        echo json_encode([$this->checkoutSession->getData(),'turnGuestQuoteToMemberQuote']);
//        exit;
        $guestQuote=$this->quoteRepository->get($guestQuoteId);
        $memberQuote=$this->quoteRepository->get($memberQuoteId);
        $this->customerSession->setFirstQuoteId($memberQuoteId);
        $this->checkoutSession->setFirstQuoteId($memberQuoteId);
        $query=$this->connection->prepare('update quote set is_active=0 where customer_id=?');
        $query->bindValue(1,$memberQuote->getCustomer()->getId());
        $query->execute();
        //save member quote info to guest quote and let guest quote become member quote and deactive member quote
        $memberQuote->setIsActive(false);
        $memberQuote->save();
        $guestQuote->setCustomer($memberQuote->getCustomer());
        $guestQuote->setBillingAddress($memberQuote->getBillingAddress());
        $guestQuote->setStoreId($memberQuote->getStoreId());
        $guestQuote->setIsActive(true);
        $guestQuote->save();
    }
    
    public function changeMemberQuoteBackToActive($memberQuoteId){
//        echo json_encode([$memberQuoteId,$this->customerSession->getFirstQuoteId(),'changeMemberQuoteBackToActive']);
//        exit;
        if(intval($memberQuoteId)<=0){
            return;
        }
        $memberQuote=$this->quoteRepository->get($memberQuoteId);
        //make first quote back to active
        $memberQuote->setIsActive(true);
        $memberQuote->save();
    }

    //cant load quoteItem directly by repositry and quote item model seems not working using sql
    public function moveItemToQuote($itemId,$quoteId,$delete=false){
        $firstQuoteId=$this->getCheckoutSession()->getFirstQuoteId();
        $secondQuoteId=$this->getCheckoutSession()->getSecondQuoteId();
        $query=$this->connection->prepare('select * from quote_item where item_id=?');
        $query->bindValue(1,$itemId);
        $query->execute();
        $result=$query->fetch();
        //for configurable product
        if($result['product_type']=='configurable') {
            $exist=$this->checkItemExistInQuote($itemId,$quoteId);
            if($exist){
                $itemExist=$this->getItemExistInQuote($itemId,$quoteId);
                // if exist merge their qty to quote
                $query=$this->connection->prepare('select * from quote_item where item_id=?');
                $query->bindValue(1,$itemId);
                $query->execute();
                $result=$query->fetch();
                $qty=$result['qty'];
                $query=$this->connection->prepare('update quote_item set qty=qty+? where item_id=?');
                $query->bindValue(1,$qty);
                $query->bindValue(2,$itemExist['item_id']);
                $query->execute();
                //remove the quote item in another bag
//                $query=$this->connection->prepare('delete from quote_item where item_id=?');
//                $query->bindValue(1,$itemId);
//                $query->execute();
            }else{
                //move item directly to another quote
                $query = $this->connection->prepare('update quote_item set quote_id=? where item_id=?');
                $query->bindValue(1, $quoteId);
                $query->bindValue(2, $itemId);
                $query->execute();
                $query = $this->connection->prepare('update quote_item set quote_id=? where parent_item_id=?');
                $query->bindValue(1, $quoteId);
                $query->bindValue(2, $itemId);
                $query->execute();
            }
        }else{
            //move itme directly to another quote
            $query = $this->connection->prepare('update quote_item set quote_id=? where item_id=?');
            $query->bindValue(1, $quoteId);
            $query->bindValue(2, $itemId);
            $query->execute();
        }
        $this->checkoutSession->getQuote()->collectTotals()->save();
        $secondQuote=$this->_objectManager->create('Magento\Quote\Model\Quote')->load($secondQuoteId);
        $secondQuote->collectTotals()->save();
//        $quote=$this->quoteRepository->get($quoteId);
//        $this->checkoutSession->clearQuote();
//        $this->checkoutSession->setQuoteId($quoteId);
//        $this->checkoutSession->getQuote()->collectTotals()->save();
        if($delete) {
            $this->deleteItem($itemId);
        }

    }

    public function checkItemExistInQuote($itemId,$quoteId){
        //get item option
        $query=$this->connection->prepare('select * from quote_item_option where code=? and item_id=?');
        $query->bindValue(1,'info_buyRequest');
        $query->bindValue(2,$itemId);
        $query->execute();
        $result=$query->fetch();
        $value=json_decode($result['value'],true);
        //get another quote item option
        $query=$this->connection->prepare('select qio.*,qi.quote_id,qi.sku,qi.qty from quote_item_option qio,quote_item qi where qio.code=? and qio.product_id=? and qi.quote_id=? and qi.item_id=qio.item_id and qio.item_id=?');
        $query->bindValue(1,'info_buyRequest');
        $query->bindValue(2,$result['product_id']);
        $query->bindValue(3,$quoteId);
        $query->bindValue(4,$result['item_id']);
        $query->execute();
        $result2=$query->fetch();
        $value2=json_decode($result2['value'],true);
        if(($result2&&$result)==false){
            return false;
        }
        //compare exist or not
        foreach ($value['super_attribute'] as $key3=>$value3){
            $value['super_attribute'][$key3]=(string)$value3;
        }

        foreach ($value2['super_attribute'] as $key3=>$value3){
            $value2['super_attribute'][$key3]=(string)$value3;
        }
        $attribute=$value['super_attribute'];
        $attribute2=$value2['super_attribute'];
        $finalResult=array_diff($attribute,$attribute2);
        return empty($finalResult);//empty = exist not empty = not exist
    }
    public function getItemExistInQuote($itemId,$quoteId){
        $query=$this->connection->prepare('select * from quote_item_option where code=? and item_id=?');
        $query->bindValue(1,'info_buyRequest');
        $query->bindValue(2,$itemId);
        $query->execute();
        $result=$query->fetch();
        $value=json_decode($result['value'],true);
        //get another quote item option
        $query=$this->connection->prepare('select qio.*,qi.sku from quote_item_option qio,quote_item qi where qio.code=? and qio.product_id=? and qi.quote_id=? and qi.item_id=qio.item_id');
        $query->bindValue(1,'info_buyRequest');
        $query->bindValue(2,$result['product_id']);
        $query->bindValue(3,$quoteId);
        $query->execute();
        return $query->fetch();
    }
    public function deleteItem($itemId){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken.'/items/'.$itemId, 'DELETE',json_encode([]),true,true);
            return $result;
        }
    }

    public function addAddressInfo($address){
        /**
         "addressInformation": {
            "shippingAddress": {
                "region": "MH",
                "region_id": 0,
                "country_id": "IN",
                "street": [
                    "Chakala,Kalyan (e)"
                ],
                "company": "abc",
                "telephone": "1111111",
                "postcode": "12223",
                "city": "Mumbai",
                "firstname": "Sameer",
                "lastname": "Sawant",
                "email": "paul@qooar.com",
                "prefix": "address_",
                "region_code": "MH",
                "sameAsBilling": 1
            },
            "billingAddress": {
                "region": "MH",
                "region_id": 0,
                "country_id": "IN",
                "street": [
                    "Chakala,Kalyan (e)"
                ],
                "company": "abc",
                "telephone": "1111111",
                "postcode": "12223",
                "city": "Mumbai",
                "firstname": "Sameer",
                "lastname": "Sawant",
                "email": "paul@qooar.com",
                "prefix": "address_",
                "region_code": "MH"
            },
            "shipping_method_code": "flatrate",
            "shipping_carrier_code": "flatrate"
        }
         */
        $this->authentication();
//        $this->createCart();
        $cartToken=$this->checkoutSession->getCartToken();
        if(isset($cartToken)) {
            $result = $this->request('guest-carts/'.$cartToken.'/shipping-information', 'POST',json_encode($address),true,true);
            return $result;
        }
        
        return [];
    }
    public function addMineCartAddress($address){
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        $customer=$this->customerRepos->getById($this->customerSession->getCustomer()->getId());
        if(isset($cartToken)) {
            $result = $this->request('carts/mine/shipping-information', 'POST',json_encode($address),true,false,$customer->getCustomAttribute('access_token')->getValue());
            return $result;
        }

        return [];
    }
    public function placeOrder($payload){
        /**
         {
            "paymentMethod": {
             "method": "checkmo"
            }
        }
         */
        $this->authentication();
        $cartToken=$this->checkoutSession->getCartToken();
        $context = $this->_objectManager->get('Magento\Framework\App\Http\Context');
        $isLoggedIn = $context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        $cacheLogin=$isLoggedIn;
        if($this->customerSession->isLoggedIn()||$cacheLogin){
            $customer=$this->customerRepos->getById($this->customerSession->getCustomerId());
            if($cacheLogin){
                $repos=$this->getCustomerRepos();
                if(!$customer->getFirstname()) {
                    $customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($context->getValue(\Cleargo\MultiCart\Model\Customer\Context::CONTEXT_CUSTOMER_ID));
                }
            }
            try {
                $this->_eventManager->dispatch('cleargo_multicart_member_placeorder_before', ['payload' => $payload]);
                if($this->getMemberApi()){
                    $result = $this->request('carts/mine/payment-information', 'POST', json_encode($payload), true, false, $customer->getCustomAttribute('access_token')->getValue());
                }else {
                    $result = $this->request('guest-carts/' . $cartToken . '/order', 'PUT', json_encode($payload), true, true);
                }
                $this->_eventManager->dispatch('cleargo_multicart_member_placeorder_after',['result'=>$result]);
            }catch (\Exception $e){
                return [
                    'result'=>'false',
                    'message'=>$e->getMessage()
                ];
            }
            //need to do this here because observer cant get current session
            if($this->getMemberApi()) {
                $this->changeMemberQuoteBackToActive($this->checkoutSession->getFirstQuoteId());
            }
            $this->getCheckoutSession()->unsCartToken();
            $this->getCheckoutSession()->unsSecondQuoteId();
            $this->customerSession->unsFirstQuoteId();

            return $result;
        }else {
            if (isset($cartToken)) {
                $result = $this->request('guest-carts/' . $cartToken . '/order', 'PUT', json_encode($payload), true, true);
                //need to do this here because observer cant get current session
                $this->changeMemberQuoteBackToActive($this->getCheckoutSession()->getFirstQuoteId());
                $this->getCheckoutSession()->unsCartToken();
                $this->getCheckoutSession()->unsSecondQuoteId();
                return $result;
            }
        }

        return false;
    }

    public function generateCustomerToken($customer){
        $token=$this->tokenFactory->create()->createCustomerToken($customer->getId());
        return $token;
    }
    
    public function getFirstQuoteId(){
        $firstQuoteId=$this->getCheckoutSession()->getFirstQuoteId();
        $secondQuoteId=$this->getCheckoutSession()->getSecondQuoteId();
        if($firstQuoteId!=$this->getCheckoutSession()->getQuoteId()){
            $secondQuoteId=$firstQuoteId;
            $firstQuoteId=$this->getCheckoutSession()->getQuoteId();
            $this->getCheckoutSession()->setFirstQuoteId($firstQuoteId);
            $this->getCheckoutSession()->setSecondQuoteId($secondQuoteId);
        }
        return $firstQuoteId;
    }

    /**
     * @return Session|\Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

}
