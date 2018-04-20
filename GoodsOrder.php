<?php
  class GoodsOrder {

    private $token;
    private $merchantId;
    private $shipmentId;
    private $orderCode;
    private $boxNumber = 0;

    public function __construct($token, $merchantId, $shipmentId, $orderCode = "") {
      $this->token = $token;
      $this->merchantId = $merchantId;
      $this->shipmentId = $shipmentId;
      $this->orderCode = $orderCode;
    }

    public function setOrderCode ($orderCode) {
      $this->orderCode = $orderCode;
    }

    public function getOrderCode () {
      return $this->orderCode;
    }    

    public function setLabel ($label) {
      $this->label = $label;
    }

    public function getShipmentId () {
      return $this->shipmentId;
    }    

    public function orderConfirm ($items) {
      $confirmItems = [];
      $rejectItems = [];
      try {
        $orderItems = $this->getOrderItems();
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }

      for($i=0; $i < count($orderItems); $i++) {
        for($j=0; $j < count($items); $j++) {
          if ($orderItems[$i]==$items[$j]) {
            $confirmItems[] = [
              "itemIndex" => $i+1, 
              "offerId" => $items[$j] 
            ];
            $orderItems[$i] = 0;
            array_splice($items, $j, 1);
            break;
          }
        }
      }
      
      for($k=0; $k < count($orderItems); $k++) {
        if ($orderItems[$k]!=0) {
          $rejectItems[] = [
            "itemIndex" => $k+1, 
            "offerId" => $orderItems[$k] 
          ];
        }
      }

      try {
        $rejectResult = $this->orderReject($rejectItems);
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
        
      if(isset($rejectResult["success"])) {
          if($rejectResult["success"] == 0|isset($rejectResult["message"])) {
            $message = $rejectResult["message"];
            $rejectResult["message"]= "Не удалось отправить order/reject на неподтвержденные лоты: ".$rejectResult["message"];
            return $rejectResult;
          }
          if(empty($confirmItems)) {
            return $rejectResult;
          }         
      }

      $data = [
        		  "meta" => [], 
        		  "data" => [
        							 "token" => $this->token, 
        							 "shipments" => [[
                                      "shipmentId" => $this->shipmentId,
                                      "orderCode" => $this->orderCode,
                                      "items" => $confirmItems 
                                      ]]
              ]
  		];
      $data_string = json_encode($data);
      $url = $this->getServiceUrl()."order/confirm";
      try {
        $result = $this->jsonPost($data_string, $url);
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
      return $this->parseResponse($result);
    }

    public function orderPacking ($items) {
      
      $packingItems = [];
      $rejectItems = [];
      $this->boxNumber = count($items);
      try {
        $orderItems = $this->getOrderItems();
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
      for($i=0; $i < count($orderItems); $i++) {
        for($j=0; $j < count($items); $j++) {
          for ($e=0; $e<count($items[$j]); $e++) {
            if ($orderItems[$i]==$items[$j][$e]) {
              $packingItems[] = [
                      "itemIndex" => $i+1, "boxes" => [
                                                        [
                                                          "boxIndex" => $j+1,
                                                          "boxCode" => $this->merchantId."*".$this->orderCode."*".($j+1)
                                                        ]                       
                                                      ]
              ];
              $orderItems[$i] = 0;
              array_splice($items[$j], $e, 1);
              break;
            }
          }  
        }
      }
      
      for($k=0; $k < count($orderItems); $k++) {
        if ($orderItems[$k]!=0) {
          $rejectItems[] = ["itemIndex" => $k+1, "offerId" => $orderItems[$k]];
        }
      }
      try {
        $rejectResult = $this->orderReject($rejectItems);
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
      if(isset($rejectResult["success"])) {
          if(($rejectResult["success"] == 0)&(isset($rejectResult["message"]))) {
            $message = $rejectResult["message"];
            $rejectResult["message"]= "Не удалось отправить order/reject на неподтвержденные лоты: ".$rejectResult["message"];

            return $rejectResult;
          }
          if(empty($rejectItems)) {
            return $rejectResult;
          }         
      }

      $data = [
              "meta" => [], 
              "data" => [
                       "token" => $this->token, 
                       "shipments" => [[
                                      "shipmentId" => $this->shipmentId,
                                      "orderCode" => $this->orderCode,
                                      "items" => $packingItems
                        ]]
              ]
      ];

      $data_string = json_encode($data);
      $url = $this->getServiceUrl()."order/packing";
            try {
        $result = $this->jsonPost($data_string, $url);
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
      return $this->parseResponse($result);
    }

    public function getOrderInfo() {
    	try {
      	$result = $this-> orderGet();
    	} catch (ErrorException $e) {
    		$result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
    	}
      $items=[];
      if(isset($result["data"]["shipments"][0])) {

        if(isset($result["data"]["shipments"][0]["orderCode"])) {
          $currentOrderCode = $result["data"]["shipments"][0]["orderCode"];
        }

        $orderInfo = [
                      "shipmentId" => $result["data"]["shipments"][0]["shipmentId"],
                      "orderCode" => $currentOrderCode,
                      "customerFullName" => $result["data"]["shipments"][0]["customerFullName"],
                      "customerAddress" => $result["data"]["shipments"][0]["customerAddress"],
                      "creationDate" => $result["data"]["shipments"][0]["creationDate"],
                      "confirmedTimeLimit" => $result["data"]["shipments"][0]["confirmedTimeLimit"],
                      "packingTimeLimit" => $result["data"]["shipments"][0]["packingTimeLimit"],
                      "shipmentDateFrom" => $result["data"]["shipments"][0]["shipmentDateFrom"],
                      "shipmentDateTo" => $result["data"]["shipments"][0]["shipmentDateTo"],
                      "deliveryDateFrom" => $result["data"]["shipments"][0]["deliveryDateFrom"],
                      "deliveryDateTo" => $result["data"]["shipments"][0]["deliveryDateTo"]
        ];
      }
      if(isset($result["data"]["shipments"][0]["items"])) {
        for($u=0;$u<count($result["data"]["shipments"][0]["items"]); $u++) {
          $status = $result["data"]["shipments"][0]["items"][$u]["status"];
          $items[] = [
                    "itemIndex" => $result["data"]["shipments"][0]["items"][$u]["itemIndex"],
                    "name" => $result["data"]["shipments"][0]["items"][$u]["goodsData"]["name"],
                    "offerId" => $result["data"]["shipments"][0]["items"][$u]["offerId"],
                    "goodsId" => $result["data"]["shipments"][0]["items"][$u]["goodsId"],
                    "status" => $result["data"]["shipments"][0]["items"][$u]["status"],
                    "subStatus" => $result["data"]["shipments"][0]["items"][$u]["subStatus"],
                    "price" => $result["data"]["shipments"][0]["items"][$u]["price"],
                    "finalPrice" => $result["data"]["shipments"][0]["items"][$u]["finalPrice"],
                    "boxIndex" => $result["data"]["shipments"][0]["items"][$u]["boxIndex"]
          ];
        }
      }
      $orderInfo["items"] = $items;
      return $orderInfo; //добавить поле success
    }

    public function getLabel() {
      $boxNumber = 0;
      $orderGetResult = $this->orderGet();
      if(isset($orderGetResult["data"]["shipments"][0]["orderCode"])) {
        $orderCode = $orderGetResult["data"]["shipments"][0]["orderCode"];
        if ($orderCode=="") {
          $result = ["success" => 0, "message" => "Не выполнена комплектация заказа."];
          return $result;
        }
      } else {
        $result = ["success" => 0, "message" => "Не выполнена комплектация заказа."];
        return $result;
      }
      if(isset($orderGetResult["data"]["shipments"][0]["items"])) {

        for($j=0; $j<count($orderGetResult["data"]["shipments"][0]["items"]); $j++) {
          if($orderGetResult["data"]["shipments"][0]["items"][$j]["boxIndex"] != null){
            if($orderGetResult["data"]["shipments"][0]["items"][$j]["boxIndex"]>$boxNumber) {
              $boxNumber = $orderGetResult["data"]["shipments"][0]["items"][$j]["boxIndex"];
            }
          }
        }
      } else {
        $result = ["success" => 0, "message" => "Не выполнена комплектация заказа."];
        return $result;
      }

      $boxes=null;
      for ($i=0; $i<$boxNumber; $i++) {
        $boxes[] = $this->merchantId."*".$orderCode."*".($i+1);
      }
      try{
        $label = $this->stickerPrint($boxes);
        return $label;
      } catch (ErrorException $e) {
        $result  = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
    }    

    private function orderReject ($rejectItems) {
      if(empty($rejectItems)) {
        return ["success" => 1];
      }
      $data = [
              "meta" => [], 
              "data" => [
                       "token" => $this->token, 
                       "shipments" => [[
                                      "shipmentId" => $this->shipmentId,
                                      "items" => $rejectItems 
                                      ]]
              ]
      ];
      $data_string = json_encode($data);
      $url = $this->getServiceUrl()."order/reject";
      try {
        $result = $this->jsonPost($data_string, $url);
      } catch (ErrorException $e) {
        $result = ["success" => 0, "message" => $e->getMessage()];
        return $result;
      }
      return $this->parseResponse($result);
    }

    private function orderGet () {

      $data = [
              "meta" => [], 
              "data" => [
                        "token" => $this->token, 
                        "shipments" => [
                                        $this->shipmentId
                        ]
              ]
      ];

      $data_string = json_encode($data);
      $url = $this->getServiceUrl()."order/get";
      $result = $this->jsonPost($data_string, $url);
      return $result;
    }

    private function getOrderItems() {
      do {
        $pendingFlag = false;
        $result = $this->orderGet();
        $items = [];
        if(isset($result["data"]["shipments"][0]["items"])) {
          for($u=0;$u<count($result["data"]["shipments"][0]["items"]); $u++) {

                $status = $result["data"]["shipments"][0]["items"][$u]["status"];
                if(strpos($status, "PENDING") !== false) { 
                  $pendingFlag = true;
                  break;
                }
                $items[] = $result["data"]["shipments"][0]["items"][$u]["offerId"];
          }
        }
      } while ($pendingFlag); //TODO some Exeption handling
        return $items;
    }

    private function getServiceUrl () {
      $config = parse_ini_file("config.ini");
      $environment = $config["environment"];
      $domain = "https://".$environment.".".$config["serviceDomain"];
      $service = $config["service"];
      return $domain.$service;
    }

    private function parseResponse($response) {
      $success;
      $result="";

        if(isset($response["success"])) {
          $success = $response["success"];
        }
        if(isset($response["data"]["result"])) {
          $result = $response["data"]["result"];
        } else if(isset($response["error"]["message"])) {
          $result = $response["error"]["message"];
        }

      return ["success" => $success, "result" => $result];
    }

    private function stickerPrint ($boxes) {
      $data = [
              "meta" => [], 
              "data" => [
                        "token" => $this->token, 
                        "shipmentId" => $this->shipmentId,
                        "boxCodes" => $boxes
              ]
      ];
      $data = json_encode($data);
      $url = $this->getServiceUrl()."sticker/print";
      $result = $this->jsonPost($data, $url);
      if(isset($result["error"])) {
        if(isset($result["error"][0]["message"])) {
          throw new ErrorException($result["error"][0]["message"]);
        } else {
          throw new ErrorException("Unknown error");
        }
      }
      if(isset($result["data"])){
        $label = $result["data"];
        return $label;
      } else {
        throw new ErrorException("Unknown error"); 
      }
    }

    private function jsonPost ($data_string, $url) {
      $ch = curl_init($url);                                                                      
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: '.strlen($data_string)));
      $res = curl_exec($ch);
      if(curl_errno($ch)) {
        throw new ErrorException(curl_error($ch));
        }
      curl_close($ch); // до ошибки?
      $result = json_decode($res, true);
      return $result;
    }
  }

?>
