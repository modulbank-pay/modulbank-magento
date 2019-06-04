<?php
namespace Modulbank\PaymentGateway\Model\Config\Source;

class PaymentMethodlist implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
				['value' => 'full_prepayment', 'label' => __('полная предоплата')],
				['value' => 'partial_prepayment', 'label' => __('частичная предоплата')],
				['value' => 'advance', 'label' => __('аванс')],
				['value' => 'full_payment', 'label' => __('полный расчет')],
				['value' => 'partial_payment', 'label' => __('частичный расчет и кредит')],
				['value' => 'credit', 'label' => __('кредит')],
				['value' => 'credit_payment', 'label' => __('выплата по кредиту')],
		];
	}
}