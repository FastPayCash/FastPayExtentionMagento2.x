<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fastpay\PayFirst\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Payment extends AbstractHelper
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'payfirst';

    /** @var \Magento\Framework\HTTP\Client\Curl $curl */
    protected $curl;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $_storeManager;

    /** @var \Magento\Config\Model\Config\Backend\Encrypted  */
    protected $bakendEncrypted;

    public $merchant_mobile_no;
    public $store_password;
    public $success_url;
    public $fail_url;
    public $cancel_url;

    public $refundUrl;
    public $paymentValidationUrl;
    public $generateTokenUrl;
	public $paymentUrl;

    protected $_gatePaymentUrlLive = "https://secure.fast-pay.cash/merchant/payment?token=";
    
    protected $_gatePaymentUrlTest = "https://dev.fast-pay.cash/merchant/payment?token=";

    protected $_gateTokenUrlLive = "https://secure.fast-pay.cash/merchant/generate-payment-token";
    
    protected $_gateTokenUrlTest = "https://dev.fast-pay.cash/merchant/generate-payment-token";

    protected $_gateValidationUrlLive = "https://secure.fast-pay.cash/merchant/payment/validation";

    protected $_gateValidationUrlTest = "https://dev.fast-pay.cash/merchant/payment/validation";


    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory  */
    protected $_orderCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\Config\Backend\Encrypted $bakendEncrypted,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($context);

        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->_storeManager = $storeManager;
        $this->bakendEncrypted = $bakendEncrypted;
        $this->_orderCollectionFactory = $orderCollectionFactory;

        $this->initConfig();
    }


    private function initConfig(){
		
		if ($this->getConfiguration('test')) {

            $this->generateTokenUrl = $this->_gateTokenUrlTest;
            $this->paymentUrl = $this->_gatePaymentUrlTest;
            $this->paymentValidationUrl = $this->_gateValidationUrlTest;
        }
        else{
			
            $this->generateTokenUrl = $this->_gateTokenUrlLive;
            $this->paymentUrl = $this->_gatePaymentUrlLive;
            $this->paymentValidationUrl = $this->_gateValidationUrlLive;
        }
     
        $this->merchant_mobile_no = $this->getConfiguration('merchant_id');
        $this->store_password = $this->getConfiguration('pass_word_1');
        $this->success_url = $this->_storeManager->getStore()->getBaseUrl().'payfirst/payment/response';//$this->getBaseUrl() 
        $this->fail_url = $this->_storeManager->getStore()->getBaseUrl().'payfirst/payment/fail';
        $this->cancel_url = $this->_storeManager->getStore()->getBaseUrl().'payfirst/payment/cancel';
        $this->ipn_url = $this->_storeManager->getStore()->getBaseUrl().'payfirst/payment/ipn';

		
    }

    public function getConfiguration($field, $storeId = null){
        if (null === $storeId) {
            $storeId = $this->_storeManager->getStore()->getId();
        }
        $path = 'payment/' . $this->_code . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }


}
