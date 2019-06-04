<?php
require_once('modulbanklib/ModulbankHelper.php');
require_once('modulbanklib/ModulbankReceipt.php');

/**
 * Register Modulbank_PaymentGateway Component
 */
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Modulbank_PaymentGateway',
    __DIR__
);
