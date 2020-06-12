<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Router;
use Auth;
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
                $user = App::get('database')->selectOne(
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
                        $user[0]['deleted']);
                        Router::respond(1, 200, 'You have successfuly logged in!', ['token' => $token, 'user' => $user[0]]);
                    else :
                        Router::respond(0, 422, 'Invalid Password!');
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
                $check_email = App::get('database')->selectOne(
                    'Users',
                    ['Email' => $email]
                );
                if ($check_email) :
                    Router::respond(0, 422, 'This email already linked to an existing account');
                    exit;
                else :
                    App::get('database')->insert('Users', [
                        'FirstName' => htmlspecialchars(strip_tags($firstname)),
                        'LastName' => htmlspecialchars(strip_tags($lastname)),
                        'Email' => $email,
                        'Pwd' => password_hash($password, PASSWORD_DEFAULT)
                    ]);
                    Router::respond(0, 201, 'Your account has been created successfuly!', ['email' => $email]);
                endif;
            endif;
        endif;
    }

    public static function isConnected()
    {
        $Auth = new Auth(App::get('database'), getallheaders());
        return $Auth->isAuth();
    }

    public function profile()
    {
        $user_id = self::isConnected();
        $user = App::get('database')->selectOne('Users', ['UserID' => $user_id]);
        if ($user) :
            unset($user[0]['Pwd'],
            $user[0]['ResetToken'],
            $user[0]['Latitude'],
            $user[0]['deleted'],
            $user[0]['Longitude']);
            Router::respond(1, 200, 'Fetched Successfuly', ['user' => $user[0]]);
        else :
            Router::respond(0, 500, 'Something went wrong');
        endif;
    }

    public function settings()
    {
        $user_id = self::isConnected();
        $user = App::get('database')->selectOne('Users', ['UserID' => $user_id]);
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
                $newuser = App::get('database')->selectOne('Users', ['UserID' => $user[0]['UserID']]);
                unset($newuser[0]['Pwd'],
                $newuser[0]['ResetToken'],
                $newuser[0]['Latitude'],
                $newuser[0]['deleted'],
                $newuser[0]['Longitude']);
                Router::respond(1, 201, 'Data updated Successfuly!', $newuser);
            endif;
        else :
            Router::respond(0, 500, 'Bearer Token must be provided!');
        endif;
    }
}
