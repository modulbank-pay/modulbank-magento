<?php
namespace Modulbank\PaymentGateway\Model\Config\Source;

class Snolist implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
	{
		return [
				['value' => 'osn', 'label' => __('общая СН')],
				['value' => 'usn_income', 'label' => __('упрощенная СН (доходы)')],
				['value' => 'usn_income_outcome', 'label' => __('упрощенная СН (доходы минус расходы)')],
				['value' => 'envd', 'label' => __('единый налог на вмененный доход')],
				['value' => 'esn', 'label' => __('единый сельскохозяйственный налог')],
				['value' => 'patent', 'label' => __('патентная СН')],
		];
	}
}