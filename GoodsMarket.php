<?php

	include_once "GoodsOrder.php";

	class GoodsMarket {

		private $token;

	    public function __construct($token) {
	      $this->token = $token;
	    }

	    protected function getToken() { //не нужно?
	    	return $this->$token;
	    }

	    public function newOrder($orderNewJson) {
	    	$data = json_decode($orderNewJson, true);
	    	$token="";
	    	$merchantId=0;
	    	$shipmentId=0;
	    	$label="";

	    	if(isset($data["data"]["merchantId"])) {
	    		$merchantId = $data["data"]["merchantId"];
	    	}
	    	if(isset($data["data"]["shipments"][0]["shipmentId"])) {
	    		$shipmentId = $data["data"]["shipments"][0]["shipmentId"];
	    	}
	    	if(isset($data["data"]["shipments"][0]["label"]["labelText"])) {
	    		$label = $data["data"]["shipments"][0]["label"]["labelText"];
	    	}
	    	$order = new GoodsOrder($this->token, $merchantId, $shipmentId);
	    	$order->setLabel($label);
	    	return $order;
	    }
	}

?>