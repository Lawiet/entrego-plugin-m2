<?php

namespace Klikealo\Entrego\Helper;

/**
 * 
 */
class Config extends \Klikealo\Carrier\Helper\Config
{
	protected $_carrierCode = 'entrego';

	const ITEMS_SMALL = "itemsSmall";
	const ITEMS_MEDIUM = "itemsMedium";
	const ITEMS_LARGE = "itemsLarge";

	public function getBoxes()
	{
		return array(
			self::ITEMS_SMALL => array(
				'width' => 12,
				'length' => 12,
				'depth' => 12
			),
			self::ITEMS_MEDIUM => array(
				'width' => 18,
				'length' => 14,
				'depth' => 14
			),
			self::ITEMS_LARGE => array(
				'width' => 24,
				'length' => 18,
				'depth' => 18
			)
		);
	}
}