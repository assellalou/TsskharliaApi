<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Router;
use Exception;

class OrdersController
{
    protected $data;

    public function book()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (
            !isset($this->data['provider']) ||
            !isset($this->data['items']) ||
            !isset($this->data['adresse']) ||
            empty($this->data['provider']) ||
            empty($this->data['items']) ||
            empty($this->data['adresse'])
        ) :
            Router::respond(0, 422, 'Please Fill in all the required fields!');
            exit;
        else :
            if (UsersController::hasOrder()) :
                Router::respond(0, 501, "A User can't have more than one order running!");
                exit;
            else :
                $items = $this->data['items'];
                $provider = $this->data['provider'];
                $adresse = $this->data['adresse'];
                $price = 0;
                $weight = 0;
                $taxOnItem = 0;
                $taxOnWeight = 0;
                foreach ($items as $item) :
                    $fullItem = self::getItem($item['id'], $provider);
                    if ($fullItem[0]) :
                        $price += (float) $fullItem[0]['ItemPrice'] * $item['quantity'];
                        $weight += (float) $fullItem[0]['ItemWeight'] * $item['quantity'];
                        $taxOnItem = ($fullItem[0]['ItemPrice'] * (0.07 * $item['quantity']));
                    else :
                        Router::respond(0, 400, 'Bad Request');
                        exit;
                    endif;
                endforeach;
                if ($weight > 3000) :
                    $additionalWeight = ($weight - 3000) / 1000;
                    for ($i = 0; $i <= $additionalWeight; $i++) {
                        $taxOnWeight += $price * 0.05;
                    }
                endif;
                $userEcoin = App::get('database')->selectBy('Users', ['UserID' => UsersController::isConnected()])[0]['ECoin'];
                $total = $price + $taxOnItem + $taxOnWeight;
                if ($userEcoin >= $total) :
                    $orderID = App::get('database')->insert('Orders', [
                        'Consumer' => UsersController::isConnected(),
                        'Amount' => $price,
                        'Tax' => $taxOnItem + $taxOnWeight,
                        'weight' => $weight,
                        'Provider' => $provider,
                        'City' => $adresse['city'],
                        'Street' => $adresse['street'],
                        'Building' => $adresse['building'],
                        'HouseNumber' => $adresse['house']
                    ]);
                    foreach ($items as $item) :
                        App::get('database')->insert('OrderedSupplies', [
                            'OrderNumber' => $orderID,
                            'ItemNumber' => $item['id'],
                            'Quantity' => $item['quantity']
                        ]);
                    endforeach;
                    $paid = $this->pay(
                        $orderID,
                        UsersController::isConnected(),
                        App::get('database')->selectBy('Users', ['Email' => App::get('config')['support'][0]])[0]['UserID'],
                        $price + $taxOnWeight + $taxOnItem
                    );
                    if ($paid) :
                        Router::respond(1, 201, "Order Published Successfuly!", ['Order' => $this->getOrder($orderID)]);
                    else :
                        Router::respond(0, 400, 'Bad Request!');
                    endif;
                else :
                    Router::respond(0, 401, 'Your Ecoin fund are insuficient for the operation!');
                endif;
            endif;
        endif;
    }

    public static function getItem($item, $provider)
    {
        return App::get('database')->selectBy('Stock', ['ItemID' => $item, 'ItemProvider' => $provider], false);
    }

    public function getOrderParticipants($uid)
    {
        return App::get('database')->selectBy('Users', [
            'UserID' => $uid
        ])[0];
    }

    public function getOrder($orderID)
    {
        $Order = App::get('database')->selectBy('Orders', ['OrderID' => $orderID], false);
        $Provider =  $this->getOrderParticipants($Order[0]['Provider']);
        $Consumer = $this->getOrderParticipants($Order[0]['Consumer']);
        if ($Order[0]['Deliveryman']) :
            $Deliveryman = $this->getOrderParticipants($Order[0]['Deliveryman']);
        else :
            $Deliveryman = null;
        endif;
        unset($Order[0]['Deleted'],
        $Order[0]['DeletionDate']);
        $Order[0]['Deliveryman'] = $Deliveryman ? " {$Deliveryman['FirstName']} {$Deliveryman['LastName']}" : null;
        $Order[0]['Consumer'] = "{$Consumer['FirstName']} {$Consumer['LastName']}";
        $Order[0]['Provider'] = "{$Provider['FirstName']} {$Provider['LastName']}";
        $items = App::get('database')->selectBy('OrderedSupplies', ['OrderNumber' => $orderID]);
        foreach ($items as $key => $value) :
            $Order[0]['Items'][$key] = App::get('database')->selectBy('Stock', ['ItemID' => $value['ItemNumber']], false)[0];
            $Order[0]['Items'][$key]['ItemQuantity'] = $value['Quantity'];
        endforeach;
        return $Order[0];
    }

    public function match()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (!isset($this->data['order']) || empty($this->data['order'])) :
            Router::respond(0, 402, 'Please require the order to match with!');
            exit;
        else :
            $order = App::get('database')->selectBy('Orders', ['OrderID' => $this->data['order']])[0];
            if ($order) :
                if (!App::get('database')->selectBy('Matche', ['OrderID' => $this->data['order']], false)) :
                    App::get('database')->insert('Matche', ['Deliveryman' => UsersController::isConnected(), 'Consumer' => $order['Consumer'], 'OrderID' => $this->data['order']]);
                    App::get('database')->modify('Orders', ['Status' => 'Buying', 'Public' => 0, 'Deliveryman' => UsersController::isConnected()], 'OrderID', $this->data['order']);
                    App::get('database')->insert('Notifications', ['ToNotify' => $order['Consumer'], 'NotifText' => 'Your order just got someone to get it done!']);
                    $matchedOrder =  $this->getOrder($this->data['order']);
                    Router::respond(1, 201, 'You have been accepted to deliver this order. Head to the provider now!', ['order' => $matchedOrder]);
                    exit;
                else :
                    Router::respond(0, 401, 'This Order just got a much! people aint waiting fella!');
                    exit;
                endif;
            else :
                Router::respond(0, 500, 'Stop fucking with us!');
                exit;
            endif;
        endif;
    }

    private function pay($orderID, $sender, $receiver, $amount)
    {

        try {
            $receiverAmount = App::get('database')->selectBy('Users', ['UserID' => $receiver], false, ['ECoin'])[0]['ECoin'];
            $senderAmount = App::get('database')->selectBy('Users', ['UserID' => $sender], false, ['ECoin'])[0]['ECoin'];
            App::get('database')->modify('Users', ['Ecoin' => $senderAmount - $amount], 'UserID', $sender);
            App::get('database')->modify('Users', ['Ecoin' => $receiverAmount + $amount], 'UserID', $receiver);
            App::get('database')->insert('EcoinTransfers', [
                'Sender' => $sender,
                'Receiver' => $receiver,
                'LinkedOrder' => $orderID,
                'Amount' => $amount
            ]);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }

    public function bought()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (UsersController::role(UsersController::isConnected()) != 3) :
            Router::respond(0, 401, 'You are not registred as a provider!');
            exit;
        else :
            if (!isset($this->data['order']) || empty($this->data['order'])) :
                Router::respond(0, 402, 'Please require the order to match with!');
                exit;
            else :
                $order = App::get('database')->selectBy('Orders', ['OrderID' => $this->data['order']])[0];
                if ($order && $order['Status'] == 'Buying') :
                    if ($order['Provider'] == UsersController::isConnected()) :
                        App::get('database')->modify('Orders', ['Status' => 'Delivering'], 'OrderID', $this->data['order']);
                        $ordereditems = App::get('database')->selectBy('OrderedSupplies', ['OrderNumber' => $order['OrderID']]);
                        $stock = App::get('database')->selectBy('Stock', ['ItemProvider' => UsersController::isConnected()], false);
                        foreach ($ordereditems as $item) :
                            foreach ($stock as $stockItem) :
                                if ($item['ItemNumber'] == $stockItem['ItemID']) :
                                    App::get('database')->modify('Stock', ['ItemQuantity' => $stockItem['ItemQuantity'] - $item['Quantity']], 'ItemID', $item['ItemNumber'], false);
                                else :
                                    continue;
                                endif;
                            endforeach;
                        endforeach;
                        Router::respond(1, 200, 'Thanks, your funds will be transfered soon!');
                        $this->pay(
                            $this->data['order'],
                            App::get('database')->selectBy('Users', ['Email' => App::get('config')['support'][0]])[0]['UserID'],
                            UsersController::isConnected(),
                            $order['Amount']
                        );
                    else :
                        Router::respond(0, 401, 'You are not the provider of this order!');
                    endif;
                else :
                    Router::respond(0, 500, 'Stop fucking with us!');
                endif;
            endif;
        endif;
    }

    public function delivered()
    {
        if (UsersController::hasOrder()) :
            $order = App::get('database')->selectBy('Orders', ['Consumer' => UsersController::isConnected()])[0];
            $DeliveryPayment = ($order['Tax'] * 55) / 100;
            if ($order['Status'] == 'Delivering') :
                App::get('database')->modify('Orders', [
                    'Status' => 'Delivered',
                    'Deleted' => 1,
                    'DeletionDate' => date("Y-m-d H:i:s")
                ], 'OrderID', $order['OrderID']);

                App::get('database')->insert('Notifications', [
                    'ToNotify' => UsersController::isConnected(),
                    'NotifText' => 'Your order has come. enjoy!'
                ]);
                App::get('database')->insert('Notifications', [
                    'ToNotify' => $order['Deliveryman'],
                    'NotifText' => 'Thanks for getting the job done. your money will be there soon!'
                ]);
                $this->pay(
                    $order['OrderID'],
                    App::get('database')->selectBy('Users', ['Email' => App::get('config')['support'][0]])[0]['UserID'],
                    $order['Deliveryman'],
                    $DeliveryPayment
                );
                if (isset($this->data['stars']) && !empty($this->data['stars']) && is_numeric($this->data['stars'])) :
                    $this->rate($order['Deliveryman'], UsersController::isConnected(), $this->data['stars']);
                endif;
            elseif ($order['Status'] == 'Delivered') :
                Router::respond(0, 401, 'Your order is delivered already!');
            else :
                Router::respond(0, 401, 'Your order still being processed!');
            endif;
        else :
            Router::respond(0, 401, 'You have no orders to take any kind of action for!');
        endif;
    }
    private function rate($deliveryman, $consumer, $stars)
    {
        App::get('database')->insert('Rates', [
            'Deliveryman' => $deliveryman,
            'Consumer' => $consumer,
            'Stars' => $stars
        ]);
    }

    public function cityOrders()
    {
        $currentUser = App::get('database')->selectBy('Users', ['UserID' => UsersController::isConnected()])[0];
        if ($currentUser != 1) :
            $orders = App::get('databse')->selectBy('Orders', ['City' => $currentUser['City']]);
            $CityOrders = [];
            foreach ($orders as $order) :
                $CityOrders[$order['OrderID']] = $this->getOrder($order['OrderID']);
            endforeach;
            Router::respond(1, 200, 'OK', ['orders' => $CityOrders]);
        else :
            Router::respond(0, 400, 'You are neither a delivery agent');
        endif;
    }

    public function getProviderItems()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (isset($this->data['provider']) && !empty($this->data['provider']) && is_numeric($this->data['provider'])) :
            $stock = App::get('database')->selectBy('Stock', ['ItemProvider' => $this->data['provider']], false);
            Router::respond(1, 200, 'OK', ['stock' => $stock]);
        else :
            Router::respond(0, 400, 'No provider provided !!');
        endif;
    }
}
