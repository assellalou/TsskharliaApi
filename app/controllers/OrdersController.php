<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Router;

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
                foreach ($items as $item) :
                    $fullItem = self::getItem($item['id'], $provider);
                    if ($fullItem[0]) :
                        $price += (float) $fullItem[0]['ItemPrice'] * $item['quantity'];
                        $weight += (float) $fullItem[0]['ItemWeight'] * $item['quantity'];
                    else :
                        Router::respond(0, 400, 'Bad Request');
                        exit;
                    endif;
                endforeach;
                $orderID = App::get('database')->insert('Orders', [
                    'Consumer' => UsersController::isConnected(),
                    'Amount' => $price,
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
                Router::respond(1, 201, "Order Published Successfuly!", ['Orders' => $this->getOrder($orderID)]);
            endif;
        endif;
    }

    public static function getItem($item, $provider)
    {
        return App::get('database')->selectBy('Stock', ['ItemID' => $item, 'ItemProvider' => $provider]);
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
            $Order[0]['Items'][$key] = App::get('database')->selectBy('Stock', ['ItemID' => $value['ItemNumber']])[0];
            $Order[0]['Items'][$key]['ItemQuantity'] = $value['Quantity'];
        endforeach;
        return $Order[0];
    }
}
