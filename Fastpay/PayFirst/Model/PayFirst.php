<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fastpay\PayFirst\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Fastpay\PayFirst\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;


/**
 * Pay In Store payment method model
 */
class PayFirst extends AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'payfirst';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Fastpay\PayFirst\Block\Form\PayFirst';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    protected $_gateUrl = "https://secure.fast-pay.cash/merchant/payment";
    
    protected $_testUrl = "https://dev.fast-pay.cash/merchant/payment";

    protected $_test;

    protected $orderFactory;
	
    /** @var \Magento\Framework\HTTP\Client\Curl $curl */
    protected $curl;

    /** @var \Fastpay\PayFirst\Helper\Payment  */
    protected $paymentHelper;

    protected $response;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		\Magento\Framework\HTTP\Client\Curl $curl,
        \Fastpay\PayFirst\Helper\Payment $paymentHelper,
         \Magento\Framework\App\Response\Http $response,
        array $data = []){
        $this->orderFactory = $orderFactory;
        $this->_request = $request;
        $this->_checkoutSession = $checkoutSession;
		 $this->curl = $curl;
        $this->paymentHelper = $paymentHelper;
        $this->response = $response;
        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);
    }


    //@param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface $payment
    public function getAmount($orderId)//\Magento\Framework\Object $payment)
    {   
        //\Magento\Sales\Model\OrderFactory
        $orderFactory=$this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

        $order = $orderFactory->create()->loadByIncrementId($orderId);
        return $order->getGrandTotal();
    }

    protected function getOrder($orderId)
    {
        $orderFactory=$this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);

    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $this->_gateUrl=$this->getConfigData('cgi_url');
        $this->_testUrl=$this->getConfigData('cgi_url_test_mode');
        $this->_test=$this->getConfigData('test');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
        );
    }

    public function getGateUrl(){
        if($this->getConfigData('test')){
            return $this->_testUrl;
        }else{
            return $this->_gateUrl;
        }
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        if(empty($shippingMethod)) {
             $shippingMethod = "No";
        }
        // return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== true;
    }

  
    public function ipnAction($response)
    {
        if($this->_request->getPost()) 
        {
            
            $orderId = $this->_checkoutSession->getLastRealOrderId();

            $amount = round($this->getAmount($orderId), 2);

            if($response['status'] == 'Success') 
            {

               $post_data = $this->getProcessPostData($orderId);
 
                $post_data = [];
                $post_data['merchant_mobile_no'] = $this->paymentHelper->merchant_mobile_no;
                $post_data['store_password'] = $this->paymentHelper->store_password;
                $post_data['order_id'] = $orderId;
                                    
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
                $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $this->curl->post($this->paymentHelper->paymentValidationUrl, $post_data);
                $result = json_decode($this->curl->getBody(), true);
                $messages = $result['messages'];
                $code = $result['code']; #if $code is not 200 then something is wrong with your request.
                $data = $result['data'];

                 if($code == '200' && $response['bill_amount']==$amount)
                    {
                            $orderState = Order::STATE_PROCESSING;
                            $order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
                    }else{

                            $orderState = Order::STATE_HOLDED;
                            $order->setState($orderState, true, 'Amount mismatch or Status code not 200')>setStatus($orderState);
                        }
                                              
              $order->save();
            }
            else 
            {
                
                $this->errorAction();
            }
      }

    }

    public function getPostData($orderId)
    {   
      
		$post_data = $this->getProcessPostData($orderId);
		$this->curl->post($this->paymentHelper->generateTokenUrl, $post_data);


        $decodedResponse = json_decode($this->curl->getBody(), true);

		$token = $decodedResponse['token'];

        $redirect_url = $this->paymentHelper->paymentUrl.$token;

        $this->response->setRedirect($redirect_url);
		return true;
    }
	

     public function responseAction($response)
    {

        if($this->_request->getPost()) 
        {
            $orderId = $this->_checkoutSession->getLastRealOrderId();

            $amount = round($this->getAmount($orderId), 2);

            if($response['status'] == 'Success') 
            {

               $post_data = $this->getProcessPostData($orderId);
 
                $post_data = [];
                $post_data['merchant_mobile_no'] = $this->paymentHelper->merchant_mobile_no;
                $post_data['store_password'] = $this->paymentHelper->store_password;
                $post_data['order_id'] = $orderId;
                                    
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
                $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $this->curl->post($this->paymentHelper->paymentValidationUrl, $post_data);
                $result = json_decode($this->curl->getBody(), true);
                $messages = $result['messages'];
                $code = $result['code']; #if $code is not 200 then something is wrong with your request.
                $data = $result['data'];

                 if($code == '200' && $response['bill_amount']==$amount)
                    {
                            $orderState = Order::STATE_PROCESSING;
                            $order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
                    }else{

                            $orderState = Order::STATE_HOLDED;
                            $order->setState($orderState, true, 'Amount mismatch or Status code not 200')>setStatus($orderState);
                        }
                                              
              $order->save();
            }
            else 
            {
                
                $this->errorAction();
            }
      }

    }
    
    public function getPaymentMethod()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        
        return $methodTitle;
    }

    public function getConfigPaymentData()
    {
        return $this->getConfigData('title');
    }
    
    public function getCusMail()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);

        $PostData['order_id'] = $orderId;
        $PostData['cus_email'] = $order->getCustomerEmail();
        $PostData['url'] = $this->getConfigData('test');
        $PostData['total_amount'] = round($this->getAmount($orderId), 2); 
        $PostData['cus_name'] = $order->getCustomerName();
        $PostData['cus_phone'] = $order->getBillingAddress()->getTelephone();
        $PostData['title'] = $this->getConfigData('title');
        $PostData['full_name'] = $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname();
        $PostData['country'] = $order->getBillingAddress()->getCountryId();
        
        // $PostData['company'] = $order->getBillingAddress()->getCompany();
        $PostData['street'] = $order->getBillingAddress()->getStreet();
        $PostData['region'] = $order->getBillingAddress()->getRegionId();
        $PostData['city'] = $order->getBillingAddress()->getCity().", ".$order->getBillingAddress()->getPostcode();
        $PostData['telephone'] = $order->getBillingAddress()->getTelephone();

        return $PostData;
    }

    public function errorAction()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $orderState = Order::STATE_CANCELED;
        $order->setState($orderState, true, 'Gateway has declined the payment.')->setStatus($orderState);
        $order->save(); 
    }
    
    public function getSuccessMsg()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
        
        $PostData=[];
        $PostData['cus_name'] = $order->getCustomerName();   
        $PostData['cus_email'] = $order->getCustomerEmail(); 
        $PostData['total_amount'] = round($this->getAmount($orderId), 2); 
        $PostData['tran_id'] = $orderId;
        $PostData['state'] = $order->getState(); 

        return $PostData;
    }
	
	
	    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function getProcessPostData($orderId){
        $postData = [];
        $postData['merchant_mobile_no'] = $this->paymentHelper->merchant_mobile_no;
        $postData['store_password'] = $this->paymentHelper->store_password;
        $postData['order_id'] = $orderId;
        $postData['bill_amount'] = round($this->getAmount($orderId), 2);
        $postData['success_url'] = $this->paymentHelper->success_url;
        $postData['fail_url'] = $this->paymentHelper->fail_url;
        $postData['cancel_url'] = $this->paymentHelper->cancel_url;

        return $postData;
    }


    
}
