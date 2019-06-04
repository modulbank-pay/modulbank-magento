/*browser:true*/
/*global define*/
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
                type: 'modulbank',
                component: 'Modulbank_PaymentGateway/js/view/payment/method-renderer/modulbank-method'
            }

        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);