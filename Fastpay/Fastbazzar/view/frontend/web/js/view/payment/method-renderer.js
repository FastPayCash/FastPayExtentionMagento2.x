define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'fastbazzar',
                component: 'Fastpay_Fastbazzar/js/view/payment/method-renderer/fastbazzar'
            }
        );
        return Component.extend({});
    }
);