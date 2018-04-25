# GoodsAPI-PHP-Library

**Инициализация**

Инициализация заказа на основе данных, полученных магазином от Goods.ru с помощью POST order/new:
```php
$goodsMarket = new GoodsMarket($token);
$json = file_get_contents("php://input"); //получение данных о новом заказе от Goods.ru
$goodsOrder = $goodsMarket->newOrder($json);
```
newOrder($json) - возвращает объект класса GoodsOrder

$json - данные запроса, полученного от Goods.ru

Инициализация заказа по номеру:
```php
$goodsOrder = new GoodsOrder($token, $merchantId, $shipmentId, $orderCode);
```
$token - токен

$merchantId - идентификатор продавца

$shipmentId - номер заказа

$orderCode - номер заказа продавца (необязательный аргумент)

**Подтверждение заказа**

Перевод указанных лотов в статус "Подтверждено".
```php
    $itemsConfirm = [106449, 70992, 106449, 47940];
    $resultConfirm = $goodsOrder->orderConfirm($itemsConfirm);
```
Возвращает массив вида:
```php
    ["success" => 1, "result" => "OK"]
```

$itemsConfirm - массив с офферами для подтверждения в любом порядке (offerId). Не переданные в массиве лоты будут отменены (отправлен order/reject)


**Комплектация заказа**

Перевод указанных лотов в статус "Скомплектовано". 
```php
    $goodsOrder->setOrderCode("abc123"); //установка номера заказа (если не был задан на этапе инициализации)
    $itemsPacking = [array(106449, 47940), array(106449)];
    $resultPacking = $goodsOrder->orderPacking($itemsPacking);
```

Возвращает массив вида:
```php
    ["success" => 1, "result" => "OK"]
```

$itemsPacking - массив массивов с офферами для комплектации (распределения по грузовым местам) в любом порядке (offerId). Не переданные в массиве лоты будут отменены (отправлен order/reject)

**Получение статуса заказа**
```php
    $result = goodsOrder->getOrderStatus();
```

Возвращает массив вида:
```php
    ["success" => 1, "result" => $orderStatus]
```

где $orderStatus - статус заказа (NEW, CONFIRMED, PACKED, MERCHANT_CANCELED, Заказ в обработке)

**Получение информации по заказу**
```php
    $result = goodsOrder->getOrderInfo();
```

Возвращает массив вида:
```php
    ["success" => 1, "result" => $orderInfo]
```

где $orderInfo - массив с подробной информацией по заказу

**Получение этикетки**
```php
    $label = $goodsOrder->getLabel();
```

Отдает этикетки по всем лотам в формате HTML. В случае, если заказ не был ранее скомплектован, возвращается шаблон этикетки без штрих-кода

Возвращает массив вида:
```php
    ["success" => 1, "result" => "label_in_html_format"]
```

**Дополнительные методы**
```php
    $goodsOrder->setOrderCode($orderCode); //Установка номера заказа
```

```php
    $orderCode = $goodsOrder->getOrderCode(); //Получение номера заказа
```

```php
    $shipmentId = $goodsOrder->getShipmentId(); //Получение номера отправления
```