<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Router;
use Auth;
use DateInterval;
use \DateTime;
use Identicon\Identicon;
use JwtHandler;

class UsersController
{
    protected $data;

    public function login()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (
            !isset($this->data['email']) ||
            !isset($this->data['password']) ||
            empty(trim($this->data['email'])) ||
            empty(trim($this->data['password']))
        ) :
            Router::respond(0, 422, 'Password and/or Email cannot be empty');
            exit;
        else :
            if (!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) :
                Router::respond(0, 422, 'Invalid Email!');
                exit;
            elseif (strlen($this->data['password']) < 8) :
                Router::respond(0, 422, 'Your Password must be at least 8 characters long!');
                exit;
            else :
                $email = trim($this->data['email']);
                $password = $this->data['password'];
                $user = App::get('database')->selectBy(
                    'Users',
                    ["Email" => $email]
                );
                if ($user) :
                    $check_password = password_verify($password, $user[0]['Pwd']);
                    if ($check_password) :
                        $jwt = new JwtHandler();
                        $token = $jwt->_jwt_encode_data(
                            'htpp://127.0.0.1:8000/',
                            array("user_id" => $user[0]['UserID'])
                        );
                        unset($user[0]['Pwd'],
                        $user[0]['ResetToken'],
                        $user[0]['Latitude'],
                        $user[0]['Longitude'],
                        $user[0]['IDNumber'],
                        $user[0]['DeletionDate'],
                        $user[0]['Deleted']);
                        $identicon = new Identicon();
                        $imgURI = $identicon->getImageDataUri($user[0]['UserID']);
                        $user[0]['profilePic'] = $imgURI;
                        Router::respond(1, 200, 'You have successfuly logged in!', ['token' => $token, 'user' => $user[0]]);
                    else :
                        Router::respond(0, 422, 'Invalid Email/Password!');
                    endif;
                else :
                    Router::respond(0, 422, 'No account linked with this email!');
                endif;
            endif;
        endif;
    }

    public function register()
    {
        $this->data = json_decode(file_get_contents("php://input"), true);
        if (
            !isset($this->data['firstname']) ||
            !isset($this->data['lastname']) ||
            !isset($this->data['email']) ||
            !isset($this->data['password']) ||
            empty(trim($this->data['firstname'])) ||
            empty(trim($this->data['lastname'])) ||
            empty(trim($this->data['email'])) ||
            empty(trim($this->data['password']))
        ) :
            Router::respond(0, 422, 'Please fill in all the fields!');
            exit;
        else :
            $firstname = trim($this->data['firstname']);
            $lastname = trim($this->data['lastname']);
            $email = trim($this->data['email']);
            $password = trim($this->data['password']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) :
                Router::respond(0, 422, 'Invalid Email!!');
                exit;
            elseif (strlen($password) < 8) :
                Router::respond(0, 422, 'Your Password must be at least 8 characters long!');
                exit;
            elseif (strlen($firstname) < 3) :
                Router::respond(0, 422, 'Your first name must be at least 3 characters long!');
                exit;
            elseif (strlen($lastname) < 3) :
                Router::respond(0, 422, 'Your last name must be at least 3 characters long!');
                exit;
            else :
                $check_email = App::get('database')->selectBy(
                    'Users',
                    ['Email' => $email]
                );
                if ($check_email) :
                    Router::respond(0, 422, 'This email already linked to an existing account');
                    exit;
                else :
                    $registredUser = App::get('database')->insert('Users', [
                        'FirstName' => htmlspecialchars(strip_tags($firstname)),
                        'LastName' => htmlspecialchars(strip_tags($lastname)),
                        'Email' => $email,
                        'Pwd' => password_hash($password, PASSWORD_DEFAULT)
                    ]);
                    Router::respond(0, 201, 'Your account has been created successfuly!', ['email' => $email]);
                    App::get('database')->insert('Notifications', ['ToNotify' => $registredUser, 'NotifText' => 'Welcome To Tsskharlia Project! thanks for registering', 'NotifLink' => '#']);
                endif;
            endif;
        endif;
    }

    public static function isConnected()
    {
        $Auth = new Auth(App::get('database'), getallheaders());
        return $Auth->isAuth();
    }

    public static function hasOrder()
    {
        $id = self::isConnected();
        $order = App::get('database')->selectBy('Orders', ['Consumer' => $id]);
        if ($order)
            return true;
        return false;
    }

    public function profile()
    {
        $user_id = self::isConnected();
        $user = App::get('database')->selectBy('Users', ['UserID' => $user_id]);
        if ($user) :
            unset($user[0]['Pwd'],
            $user[0]['ResetToken'],
            $user[0]['Latitude'],
            $user[0]['Deleted'],
            $user[0]['DeletionDate'],
            $user[0]['Longitude']);
            $identicon = new Identicon();
            $imgURI = $identicon->getImageDataUri($user[0]['UserID']);
            $user[0]['profilePic'] = $imgURI;
            Router::respond(1, 200, 'Fetched Successfuly', ['user' => $user[0]]);
        else :
            Router::respond(0, 500, 'Something went wrong');
        endif;
    }

    public function settings()
    {
        $user_id = self::isConnected();
        $user = App::get('database')->selectBy('Users', ['UserID' => $user_id]);
        $this->data = json_decode(file_get_contents("php://input"), true);
        $err = false;
        if ($user) :
            if (isset($this->data['firstname']) && !empty($this->data['firstname'])) :
                $firstname = htmlspecialchars(strip_tags(stripslashes(trim($this->data['firstname']))));
                if (strlen($firstname) < 3) :
                    Router::respond(0, 402, 'First Name must be at least 3 chars long!');
                    $err = true;
                    exit;
                else :
                    App::get('database')->modify(
                        'Users',
                        ['FirstName' => $firstname],
                        'UserID',
                        $user_id
                    );
                endif;
            endif;
            if (isset($this->data['lastname']) && !empty($this->data['lastname'])) :
                $lastname = htmlspecialchars(strip_tags(stripslashes(trim($this->data['lastname']))));
                if (strlen($lastname) < 3) :
                    Router::respond(0, 402, 'Last Name must be at least 3 chars long!');
                    $err = true;
                    exit;
                else :
                    App::get('database')->modify(
                        'Users',
                        ['LastName' => $lastname],
                        'UserID',
                        $user_id
                    );
                endif;
            endif;
            if (isset($this->data['city']) && !empty($this->data['city'])) :
                $city = htmlspecialchars(strip_tags(stripslashes(trim($this->data['city']))));
                App::get('database')->modify('Users', ['City' => $city], 'UserID', $user_id);
            endif;
            if (isset($this->data['street']) && !empty($this->data['street'])) :
                $street = htmlspecialchars(strip_tags(stripslashes(trim($this->data['street']))));
                App::get('database')->modify('Users', ['Street' => $street], 'UserID', $user_id);
            endif;
            if (isset($this->data['building']) && !empty($this->data['building'])) :
                $building  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['building']))));
                App::get('database')->modify('Users', ['Building' => $building], 'UserID', $user_id);
            endif;
            if (isset($this->data['housenumber']) && !empty($this->data['housenumber'])) :
                $housenumber  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['housenumber']))));
                App::get('database')->modify('Users', ['HouseNumber' => $housenumber], 'UserID', $user_id);
            endif;
            if (isset($this->data['zipcode']) && !empty($this->data['zipcode'])) :
                $zipcode  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['zipcode']))));
                App::get('database')->modify('Users', ['ZipCode' => $zipcode], 'UserID', $user_id);
            endif;
            if (isset($this->data['region']) && !empty($this->data['region'])) :
                $region  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['region']))));
                App::get('database')->modify('Users', ['Region' => $region], 'UserID', $user_id);
            endif;
            if (isset($this->data['gender']) && !empty($this->data['gender'])) :
                $gender  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['gender']))));
                App::get('database')->modify('Users', ['Gender' => $gender], 'UserID', $user_id);
            endif;
            if (isset($this->data['phone']) && !empty($this->data['phone']) && is_numeric($this->data['phone'])) :
                $phone = htmlspecialchars(strip_tags(stripslashes(trim($this->data['phone']))));
                App::get('database')->modify('Users', ['Phone' => $phone], 'UserID', $user_id);
            endif;
            if (isset($this->data['birthyear']) && !empty($this->data['birthyear']) && is_numeric($this->data['phone'])) :
                $birthyear = htmlspecialchars(strip_tags(stripslashes(trim($this->data['birthyear']))));
                App::get('database')->modify('Users', ['BirthYear' => $birthyear], 'UserID', $user_id);
            endif;
            if (isset($this->data['birthmonth']) && !empty($this->data['birthmonth']) && is_numeric($this->data['phone'])) :
                $birthmonth = htmlspecialchars(strip_tags(stripslashes(trim($this->data['birthmonth']))));
                App::get('database')->modify('Users', ['BirthMonth' => $birthmonth], 'UserID', $user_id);
            endif;
            if (isset($this->data['birthday']) && !empty($this->data['birthday']) && is_numeric($this->data['phone'])) :
                $birthday = htmlspecialchars(strip_tags(stripslashes(trim($this->data['birthday']))));
                App::get('database')->modify('Users', ['BirthDay' => $birthday], 'UserID', $user_id);
            endif;
            if (isset($this->data['idnumber']) && !empty($this->data['idnumber'])) :
                $idnumber  = htmlspecialchars(strip_tags(stripslashes(trim($this->data['idnumber']))));
                $cinExists = App::get('database')->selectBy('Users', ['IDNumber' => $idnumber], false);
                if ($cinExists) {
                    Router::respond(0, 402, 'This ID number is already linked to an account!');
                    exit;
                }
                App::get('database')->modify('Users', ['IDNumber' => $idnumber], 'UserID', $user_id);
            endif;
            if (
                isset($this->data['oldpassword']) &&
                !empty($this->data['oldpassword']) &&
                isset($this->data['newpassword']) &&
                !empty($this->data['newpassword'])
            ) :
                $newpassword = $this->data['newpassword'];
                $oldpassword = $this->data['oldpassword'];
                if (strlen($newpassword) < 8) :
                    Router::respond(0, 402, 'New Password must be at least 8 chars long!');
                    $err = true;
                    exit;
                else :
                    if (password_verify($oldpassword, $user[0]['Pwd'])) :
                        App::get('database')->modify(
                            'Users',
                            ['Pwd' => password_hash($newpassword, PASSWORD_DEFAULT)],
                            'UserID',
                            $user_id
                        );
                    else :
                        Router::respond(0, 402, 'Old Password is incorrect!');
                        $err = true;
                    endif;
                endif;
            endif;
            if (!$err) :
                $newuser = App::get('database')->selectBy('Users', ['UserID' => $user[0]['UserID']]);
                unset($newuser[0]['Pwd'],
                $newuser[0]['ResetToken'],
                $newuser[0]['Latitude'],
                $newuser[0]['Deleted'],
                $newuser[0]['DeletionDate'],
                $newuser[0]['LastCheck'],
                $newuser[0]['Longitude']);
                $identicon = new Identicon();
                $imgURI = $identicon->getImageDataUri($user[0]['UserID']);
                $user[0]['profilePic'] = $imgURI;
                Router::respond(1, 201, 'Data updated Successfuly!', $newuser);
            endif;
        else :
            Router::respond(0, 500, 'Bearer Token must be provided!');
        endif;
    }


    public function getOrder($orderID)
    {
        $Order = App::get('database')->selectBy('Orders', ['OrderID' => $orderID], false);
        $Provider =  App::get('database')->selectBy('Users', ['UserID' => $Order[0]['Provider']])[0];
        $Consumer = App::get('database')->selectBy('Users', ['UserID' => $Order[0]['Consumer']])[0];
        if ($Order[0]['Deliveryman']) :
            $Deliveryman = App::get('database')->selectBy('Users', ['UserID' => $Order[0]['Deliveryman']])[0];
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

    public function check()
    {
        $user = App::get('database')->selectBy('Users', ['UserID' => UsersController::isConnected()])[0];
        $notifs = App::get('database')->selectBy('Notifications', ['ToNotify' => UsersController::isConnected()], false, ['NotifID', 'NotifText', 'NotifLink']);
        $date = date("Y-m-d H:i:s");
        App::get('database')->modify(
            'Users',
            ['LastCheck' => $date],
            'UserID',
            UsersController::isConnected()
        );
        if ($user['CurrentRole'] == 1) : //consumer
            $order = App::get('database')->selectBy('Orders', ['Consumer' => UsersController::isConnected()])[0];
            Router::respond(1, 200, 'OK', ['Notifications' => $notifs, 'Order' => $order]);
        elseif ($user['CurrentRole'] == 3) : //provider

            $ordered = App::get('database')->selectBy('Orders', ['Provider' => UsersController::isConnected()]);
            if ($ordered) :
                $orderedItems = [];
                foreach ($ordered as $ord) :
                    $orderedItems[$ord['OrderID']] = $this->getOrder($ord['OrderID']);
                endforeach;
                Router::respond(1, 200, 'OK', ['Notifications' => $notifs, 'Orders' => $orderedItems]);
            else :
                Router::respond(1, 200, 'OK', ['Notifications' => $notifs]);
            endif;
        elseif ($user['CurrentRole'] == 2) : //deliverman
            $curOrder = App::get('database')->selectBy('Orders', ['Deliveryman' => UsersController::isConnected()]);
            if ($curOrder) :
                $consumer = App::get('database')->selectBy('Users', ['UserID' => $curOrder[0]['Consumer']])[0];
                $provider = App::get('database')->selectBy('Users', ['UserID' => $curOrder[0]['Provider']], true, ['FirstName', 'LastName', 'Phone', 'City', 'Street', 'Building', 'HouseNumber'])[0];
                $curOrder[0]['ConsumerName'] = $consumer['FirstName'] . ' ' . $consumer['LastName'];
                $curOrder[0]['ConsumerPhone'] = $consumer['Phone'];
                $curOrder[0]['Provider'] = $provider;
                Router::respond(1, 200, 'OK', ['Notifications' => $notifs, 'Orders' => $curOrder[0]]);
            else :
                Router::respond(1, 200, 'OK', ['Notifications' => $notifs]);
            endif;
        else :
            Router::respond(1, 200, 'OK', ['Notifications' => $notifs]);
        endif;

        // Router::respond(1, 200, 'OK', ['Notifications' => $notifs[0]]); um sorry cuz
    }

    public static function format_interval(DateInterval $interval)
    {
        $result = "";
        if ($interval->y) {
            $result .= $interval->format("%y years ");
        }
        if ($interval->m) {
            $result .= $interval->format("%m months ");
        }
        if ($interval->d) {
            $result .= $interval->format("%d days ");
        }
        if ($interval->h) {
            $result .= $interval->format("%h hours ");
        }
        if ($interval->i) {
            $result .= $interval->format("%i minutes ");
        }
        if ($interval->s) {
            $result .= $interval->format("%s seconds ");
        }
        return $result;
    }

    public static function role($id)
    {
        return App::get('database')->selectBy('Users', ['UserID' => $id], true, ['CurrentRole'])[0]['CurrentRole'];
    }

    public static function isOnline($id)
    {
        $user = App::get('database')->selectBy('Users', ['UserID' => $id]);
        if ($user) :
            $lastCheckString = strtotime($user[0]['LastCheck']);
            $lastCheckDate = new DateTime();
            $lastCheckDate->setTimestamp($lastCheckString);
            $now = new DateTime();
            $diff = $now->diff($lastCheckDate);
            if ($diff->y || $diff->m || $diff->d || $diff->h) :
                return false;
            elseif ($diff->i && $diff->i > 5) :
                return false;
            else :
                return true;
            endif;
        else :
            Router::respond(0, 404, 'User Not Found!');
            exit;
        endif;
    }

    public function getProviders()
    {
        $userCity = App::get('database')->selectBy('Users', ['UserID' => UsersController::isConnected()])[0]['City'];
        $providers = App::get('database')->selectBy('Users', ['City' => $userCity, 'CurrentRole' => 3], true, ['UserID', 'FirstName', 'LastName']);
        if ($providers) :
            Router::respond(1, 200, 'OK', ['providers' => $providers]);
        else :
            Router::respond(0, 400, 'No providers in your area!');
        endif;
    }
}
