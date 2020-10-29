<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fastpay\PayFirst\Controller\Payment;
use Magento\Framework\Controller\ResultFactory;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Ipn extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }
    /**
     * Load the page defined in view/frontend/layout/samplenewpage_index_index.xml
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {   //load model
        /* @var $paymentMethod \Magento\Authorizenet\Model\DirectPost */
        $paymentMethod = $this->_objectManager->create('Fastpay\PayFirst\Model\PayFirst');

        if(!empty($this->getRequest()->getPostValue()))
        {
            $data = $this->getRequest()->getPostValue();
            
            $resp = $paymentMethod->ipnAction($data);
            
            $ipn_log = fopen("Fastpay_IPN_LOG.txt", "a+") or die("Unable to open file!");
           
            fwrite($ipn_log, json_encode($data).PHP_EOL);

            fclose($ipn_log);
        }
        else
        {
            echo "<span align='center'>
                  <h2>IPN only accept POST request!</h2>
                  <p>Remember, We have set an IPN URL in first step so that your server can listen at the right moment when payment is done at FastPay End. So, It is important to validate the transaction notification to maintain security and standard.As IPN URL already set in script. All the payment notification will reach through IPN prior to user return back. So it needs validation for amount and transaction properly.</p>
                  </span>";
        }
    }
}
