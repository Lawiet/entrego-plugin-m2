<?php

namespace Klikealo\Entrego\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
	protected $_carrierFactory;

	public function __construct(\Klikealo\Carrier\Model\CarrierFactory $carrierFactory)
	{
		$this->_carrierFactory = $carrierFactory;
	}

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$data = [
			'name'		=> "Entrego",
			'code' 		=> "entrego",
			'document'	=> '',
			'status'       => 1
		];
		
		$carrier = $this->_carrierFactory->create();
		$carrier->addData($data)->save();
	}
}