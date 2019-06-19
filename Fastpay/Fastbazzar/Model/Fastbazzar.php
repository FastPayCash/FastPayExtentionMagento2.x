<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fastpay\Fastbazzar\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Fastpay\Fastbazzar\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;


/**
 * Pay In Store payment method model
 */
class Fastbazzar extends AbstractMethod
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
    protected $_code = 'fastbazzar';

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
    protected $_formBlockType = 'Fastpay\Fastbazzar\Block\Form\Fastbazzar';

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
        array $data = []){
        $this->orderFactory = $orderFactory;
        $this->_request = $request;
        $this->_checkoutSession = $checkoutSession;
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
    {   //\Magento\Sales\Model\OrderFactory
        $orderFactory=$this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

            $order = $orderFactory->create()->loadByIncrementId($orderId);
            //$payment= $order->getPayment();

        // return $payment->getAmount();
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
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== true;
    }


    public function ipn_hash_varify($store_passwd="", $data="") 
    {
        if(isset($data) && isset($data['verify_sign']) && isset($data['verify_key'])) 
        {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $data['verify_key']);
            $new_data = array();
            if(!empty($pre_define_key )) 
            {
                foreach($pre_define_key as $value) 
                {
                    if(isset($data[$value])) 
                    {
                        $new_data[$value] = ($data[$value]);
                    }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = ($store_passwd);
            # SORT THE KEY AS BEFORE
            ksort($new_data);
            $hash_string="";
            foreach($new_data as $key=>$value) { $hash_string .= $key.'='.($value).'&'; }
            $hash_string = rtrim($hash_string,'&');
            if(md5($hash_string) == $data['verify_sign']) 
            {
                return true;
            } 
            else 
            {
                return false;
            }
        } 
        else 
            return false;
    }

    public function ipnAction($postData){
        
        $password = md5($this->getConfigData('pass_word_1'));
        $result = "No Result";
        if($this->_request->getPost())
        {
            if($this->ipn_hash_varify($password, $postData)) 
            {
                $result = "Hash validation success.";
            }
            else 
            {
                $result = "Hash validation failed.";
            }
        }
        echo $result;
    }

    public function getPostData($orderId)
    {   
        //TODO: add curency
        //OutSumCurrency
        //$order = $observer->getEvent()->getOrder();
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId); //Use increment id here.
        //$order->getCustomerName(); 
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
        //$currentStore = $storeManager->getStore();
        //$baseUrl = $storeManager->getStore()->getBaseUrl();
		
		if ($this->getConfigData('test')) {
            $direct_api_url = "https://dev.fast-pay.cash/merchant/generate-payment-token";
        }
        else{
            $direct_api_url = "https://secure.fast-pay.cash/merchant/generate-payment-token";
        }
    
    
    
        $PostData=[];
		$post_data = [];
        $PostData['OutSum']=round($this->getAmount($orderId), 2);
        $PostData['InvId']=intval($orderId);
    
        //sscl    
		
		$post_data['merchant_mobile_no'] = $this->getConfigData('merchant_id');
		$post_data['store_password'] = $this->getConfigData('pass_word_1');
		$post_data['order_id'] = $orderId;
		$post_data['bill_amount'] = round($this->getAmount($orderId), 2);

        #$PostData['store_id']=$this->getConfigData('merchant_id');
        #$PostData['store_passwd']=$this->getConfigData('pass_word_1');
        #$PostData['total_amount']=round($this->getAmount($orderId), 2); 
        #$PostData['tran_id']=$orderId;  
        #$PostData['currency']=  $storeManager->getStore()->getCurrentCurrency()->getCode(); //$this->getConfigData('currency');
		
        $post_data['success_url']=$storeManager->getStore()->getBaseUrl().'fastbazzar/payment/response';//$this->getBaseUrl() 
        $post_data['fail_url']=$storeManager->getStore()->getBaseUrl().'fastbazzar/payment/fail';
        $post_data['cancel_url']=$storeManager->getStore()->getBaseUrl().'fastbazzar/payment/cancel';

			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $direct_api_url );
			curl_setopt($handle, CURLOPT_TIMEOUT, 10);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($handle, CURLOPT_POST, 1 );
			curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);


			$content = curl_exec($handle );

			$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

			if($code == 200 && !( curl_errno($handle))) {
				curl_close( $handle);
				$response = $content;
			} else {
				curl_close( $handle);
				echo "FAILED TO CONNECT WITH FastPay  API";
				exit;
			}
			
        $decodedResponse = json_decode($response, true );
		$PostData['token'] = $decodedResponse['token'];
		

        // CUSTOMER INFORMATION 
        #$PostData['cus_name'] =$order->getCustomerName();   
        #$PostData['cus_email'] =$order->getCustomerEmail();
        #$PostData['cus_phone'] =$order->getBillingAddress()->getTelephone();
    
    
        #$PostData['Description']="payment for order ".$orderId;
       
        #$password = $PostData['store_passwd']=$this->getConfigData('pass_word_1');

   
        return $PostData;

    }

    public function responseAction($response)
    {
        if ($this->getConfigData('test')) {
            $validUrl = "https://dev.fast-pay.cash/merchant/payment/validation";
        }
        else{
            $validUrl = "https://secure.fast-pay.cash/merchant/payment/validation";
        }

        if($this->_request->getPost()) 
        {
            $orderId = $this->_checkoutSession->getLastRealOrderId();

            if($response['status'] == 'Success') 
            {
 
				$post_data = array();
				$post_data['merchant_mobile_no']= $this->getConfigData('merchant_id');
				$post_data['store_password']= $this->getConfigData('pass_word_1');
				$post_data['order_id']= $orderId;
									
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
                $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
									
									$requested_url = $validUrl;

									$handle = curl_init();
									curl_setopt($handle, CURLOPT_URL, $requested_url );
									curl_setopt($handle, CURLOPT_TIMEOUT, 10);
									curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
									curl_setopt($handle, CURLOPT_POST, 1 );
									curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
									curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

									$result = curl_exec($handle);

									$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

									if($code == 200 && !( curl_errno($handle)))
									{

										# TO CONVERT AS ARRAY
										# $result = json_decode($result, true);

										# TO CONVERT AS OBJECT
									$result = json_decode($result);

										# TRANSACTION INFO
									$messages = $result->messages;
									$code = $result->code; #if $code is not 200 then something is wrong with your request.
									$data = $result->data;
									
									
										
								           if($code == '200')	{
															$orderState = Order::STATE_PROCESSING;
															$order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
														}
														else
														{
															$orderState = Order::STATE_HOLDED;
															$order->setState($orderState, true, $risk_title)>setStatus($orderState);
														}
														
														
														//$this->getSuccessMsg();
										

									} else {

										 $this->errorAction();
									}  
              $order->save();
            }
            else 
            {
                // There is a problem in the response we got
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
        // $PostData['cus_phone'] = $order->getBillingAddress()->getTelephone();  
        $PostData['total_amount'] = round($this->getAmount($orderId), 2); 
        $PostData['tran_id'] = $orderId;
        $PostData['state'] = $order->getState(); 

        return $PostData;
		
		
		
    }
	
	

}
