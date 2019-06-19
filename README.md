Magento 2 Extension for FastPay Online Payment Module
---------------------------------------------------------------------------------

FastPay is Iraq’s quickest, most convenient, and safest mobile wallet where millions of customers will have their smart money for Shopping,
Mobile recharge, Internet recharge, Money transfers, Bill payments and more

This Module Work for Magento Version 2.x.x

Installation Steps

Please follow these steps to install the FastPay Payment Gateway module.

Step 1: Download module from https://github.com/letoncse7/FastPayMagento2Extension.git

Step 2: Log in to your server using FTP or Cpanel.

Step 3: Upload FastPay (Magento 2 Payment Module) inside of app/code dirrectory.

Step 4: Now Run the command line and go to your project root directory.

Step 5: Run The following command into command line: -

	5.1: php bin/magento setup:upgrade
	5.2: php bin/magento setup:static-content:deploy -f
	5.3: php bin/magento cache:clean
	5.4: php bin/magento cache:flush
	5.5: php bin/magento index:reindex

Step 6: Navigate to SYSTEM> Cache Management: Select all Cache type and click on Flush Magento Cache. Or you can do step 5.3, 5.4 & 5.5 to update module and flush cache.

Step 7: Log in to your Magento admin account.



Step 8: Navigate to STORES > Configuration > SALES > Payment Methods > Other Payment Methods: > Fastpay Payment Gateway : then follow below steps

- Enabled : Yes (If No then Make it Yes).
- Title : Set your own title what you want to see in checkout page.
- New Order Status : Pending Payment.
- Test Mode : Yes (If No then Make it Yes).
- Allowed Shipping Methods : Your Shipping Methods if have any.
- Merchant ID : Your Valid Merchant ID that Provided from FastPay.
- API Password : Your Valid Password that Provided from FastPay.
- Payment from Applicable Countries : Default / If haven't any. 
- Payment from Specific Countries : Default / If haven't any. 
- Instructions : Your Instructions.
- Sort Order : It can be empty.

Now Click on Save Config button.

Step 9: Navigate to SYSTEM> Cache Management: Select all Cache type and click on Flush Magento Cache. Or you can do step 5.3, 5.4 & 5.5 to update module and flush cache.

Step 10: Now you can test module is working or not. If you can see your payment option at checkout page then you have installed Module successfully :)

Step 11: Do a test transaction.

Thank You!

*** Note : If your are using Magento email service then you can use this module.

For any issue, feel free to contact any time.

- Author : FastPay
- Developed By : Leton Miah
- Email : letoncse7@gmail.com
- Team-Email: integration@fast-pay.cash
- More info: https://secure.fast-pay.cash/docs


© 2019 FastPay ALL RIGHTS RESERVED
