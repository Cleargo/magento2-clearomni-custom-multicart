<?php

namespace Cleargo\MultiCart\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    protected $helper;
    protected $customerRepos;

    public function __construct(
        \Cleargo\MultiCart\Helper\Data $helper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->helper     = $helper;
        $this->customerRepos=$customerRepositoryInterface;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $token=$this->helper->generateCustomerToken($customer);
        $dataModel=$customer->getDataModel();
        $dataModel->setCustomAttribute('access_token', $token->getToken());
        $this->customerRepos->save($dataModel);
//        var_dump('aweawcc');
//        exit;
    }
}
