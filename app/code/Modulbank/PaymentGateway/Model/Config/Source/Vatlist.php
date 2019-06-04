<?php
namespace Modulbank\PaymentGateway\Model\Config\Source;

class Vatlist implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
				['value' => 'none', 'label' => __('без НДС')],
				['value' => 'vat0', 'label' => __('НДС по ставке 0%')],
				['value' => 'vat10', 'label' => __('НДС чека по ставке 10%')],
				['value' => 'vat20', 'label' => __('НДС чека по ставке 20%')],
				['value' => 'vat110', 'label' => __('НДС чека по расчетной ставке 10/110')],
				['value' => 'vat120', 'label' => __('НДС чека по расчетной ставке 20/120')],
		];
	}
}