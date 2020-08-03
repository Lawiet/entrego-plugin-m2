<?php

namespace Klikealo\Entrego\Cron;

/**
 * 		
 */
class Monitor extends \Klikealo\Carrier\Cron\Monitor
{
	private $logger;

	public function __construct(
		\Entrego\Rest $rest,
		\Entrego\Delivery $carrier,
		\Klikealo\Entrego\Helper\Config $config,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
	)
	{
		$this->rest = $rest;
		$this->carrier = $carrier;
		$this->configHelper = $config;
		$this->orderCollectionFactory = $orderCollectionFactory;

        $this->logger = $logger;
        
        parent::__construct($convertOrder, $shipmentNotifier, $trackFactory);

		$this->init();
	}

	public function init()
	{
		if(!$this->configHelper->getConfig('active')) return;

		$this->rest->email = $this->configHelper->getConfig('email');
		$this->rest->password = $this->configHelper->getConfig('password');
		$this->rest->sandbox = $this->configHelper->getConfig('sandbox');
		
		$this->carrier->setRest($this->rest);
	}

	public function notifyOrders()
	{
		if(!$this->configHelper->getConfig('active')) return;

		$orderCollection = $this->orderCollectionFactory->create();

		$orderCollection->addFieldToFilter('status', $this->configHelper->getConfig('order_status'))
							->addFieldToFilter('shipping_method', array(
								array('like' => "%entrego%")
							))
							->setOrder('created_at', 'desc');

		foreach ($orderCollection as $order) {
			try{
				if(!$order->canShip()) {
					throw new \Magento\Framework\Exception\LocalizedException(__('You can\'t create the Shipment of this order.'));
				}

				$shippingAddress = $order->getShippingAddress();

				$boxes = array(
					\Klikealo\Entrego\Helper\Config::ITEMS_SMALL => 0,
					\Klikealo\Entrego\Helper\Config::ITEMS_MEDIUM => 0,
					\Klikealo\Entrego\Helper\Config::ITEMS_LARGE => 0
				);

				foreach ($order->getAllItems() as $product) {
					$tsDimensionsLength = $product->getTsDimensionsLength();
					$tsDimensionsWidth = $product->getTsDimensionsWidth();
					$tsDimensionsHeight = $product->getTsDimensionsHeight();

					foreach ($this->configHelper->getBoxes() as $typeBox => $dimension) {
						if($tsDimensionsWidth <= $dimension['width'] && $tsDimensionsHeight <= $dimension['length'] && $tsDimensionsLength <= $dimension['depth']) {
							$boxes[$typeBox] = $boxes[$typeBox] + $product->getQtyOrdered();
							$this->logger->info('Qty', ['sad' => $product->getQtyOrdered()]);
							break;
						}
					}
				}

				$payment = $order->getPayment();
				$methodTitle = $payment->getMethod();
				$observacion = "";

				if($methodTitle == "cashondelivery") {
					$observacion = "Contraentrega, cliente paga con {$payment->getData('po_number')}";
				}
				
				$responseEntrego = $this->carrier->createNewDelivery($jsonRequest = array(
					'addresses' => array(
						array(
							'address' => $this->configHelper->getConfigMain('address'),
							'notes' => $this->configHelper->getConfigMain('comment')
						),
						array(
							'address' => implode(",", $shippingAddress->getStreet()),
							'notes' => 'Destino'
						)
					),
					'parcel' => $this->configHelper->getConfig('parcel'),
					'category' => 'INTERCITY',
					'type' => $this->configHelper->getConfig('shipping_type'),
					'payment' => array(
						'type' => $this->configHelper->getConfig('payment')
					),
					'intercity' => array(
						'remitent' => array(
							'type' => 0,
							'documentId' => $this->configHelper->getConfigMain('ruc'),
							'documentType' => '',
							'phone' => $this->configHelper->getConfigMain('phone'),
							'name' => $this->configHelper->getConfigMain('name')
						),
						'recipient' => array(
							'type' => 0,
							'documentId' => '',
							'documentType' => '',
							'phone' => $shippingAddress->getTelephone(),
							'name' => "{$shippingAddress->getFirstName()} {$shippingAddress->getLastName()}"
						)
					),
					'itemsDocuments' => 0,
					'itemsSmall' => $boxes[\Klikealo\Entrego\Helper\Config::ITEMS_SMALL],
					'itemsMedium' => $boxes[\Klikealo\Entrego\Helper\Config::ITEMS_MEDIUM],
					'itemsLarge' => $boxes[\Klikealo\Entrego\Helper\Config::ITEMS_LARGE],
					'description' => $observacion
				));

				$this->logger->info('Info', ['response' => $responseEntrego]);

	            $this->generateShipment($order, array(
	            	/*array(
		            	'carrier_code' => 'custom',
					    'title' => 'Url tracking',
					    'number' => $responseEntrego->payload->id
		            ),*/
		            array(
		            	'carrier_code' => 'custom',
					    'title' => 'Code tracking',
					    'number' => $responseEntrego->payload->id
		            )
	            ));

	            $this->carrier->deliveryConfirmationRequest($responseEntrego->payload->id);
	            $this->logger->info($order->getIncrementId(), array('json_request' => $jsonRequest));
			}catch(\Exception $e) {
				$order->addStatusHistoryComment($e->getMessage());
				$this->logger->critical('Error message', ['order' => $order->getIncrementId(), 'json_request' => isset($jsonRequest) ? json_encode($jsonRequest) : "", 'exception' => $e, "user" => "{$this->rest->email}:{$this->rest->password}"]);
			}finally {
				$order->save();
				unset($shippingAddress, $responseEntrego, $jsonRequest);
			}
		}
	}

	public function getTracking()
	{
		if(!$this->configHelper->getConfig('active')) return;
	}
}