# magento2-payfast

## What are the installation requirements?
*  A working Magento 2.0 、2.1 or 2.2 installation

## INSTALLATION AND TESTING

#### How do I install the PayFast module?
* 1、Setup ZAR on your Magento site. In the admin panel navigate to ‘Stores’, and add ZAR under currency Symbols and Rates.
* 2、Download the PayFast module for Magento 2.0 、Magento 2.1 or Magento 2.2
* 3、Copy the PayFast app folder to your root Magento folder. This will not overwrite any files on your system.
* 4、You will now need to run the following commands in the given order:
    *  php ./bin/magento module:enable Payfast_Payfast
    *  php ./bin/magento setup:di:compile
    *  php ./bin/magento setup:static-content:deploy
    *  php ./bin/magento cache:clean
* 5、Log into the admin panel and navigate to 'Stores'>'Configuration'>'Sales'>'Payment Method' and click on Payfast
* 6、Enable the module, as well as debugging.
    *  To test in sandbox insert 'test' in the 'server' field and use the following credentials: which you have a test account in sandbox Leave the passphrase blank and setup the other options as required
* 7、Click 'Save Config', you are now ready to test in sandbox, click 'Save Config'
* 8、Once you are ready to go live, insert 'live' into the 'server' field and input your PayFast credentials. Set debug log to 'No', and the other options as required
* 9、Click 'Save Config', you are now ready to process live transactions via PayFast

### Testing with the Sandbox
* URL: https://sandbox.payfast.co.za
* more information in: https://developers.payfast.co.za/documentation/#ports

 ### Based on [https://github.com/PayFast/mod-magento_2]
