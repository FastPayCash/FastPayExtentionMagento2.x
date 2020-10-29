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
                type: 'payfirst',
                component: 'Fastpay_PayFirst/js/view/payment/method-renderer/payfirst'
            }
        );
        return Component.extend({});
    }
);