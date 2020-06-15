<?php

use \Firebase\JWT\JWT;
use App\Core\App;

class JwtHandler
{
    protected $jwt_secret;
    protected $token;
    protected $issuedAt;
    protected $expire;
    protected $jwt;

    public function __construct()
    {
        date_default_timezone_set('Africa/Casablanca');
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + 6000;
        $this->jwt_secret = App::get('config')['jwt_secret_key'];
    }
    //encoding the damned token
    public function _jwt_encode_data($iss, $data)
    {
        $this->token = array(
            //identifier
            "iss" => $iss,
            "aud" => $iss,
            //issued date
            "iat" => $this->issuedAt,
            //expiration date
            "exp" => $this->expire,
            //payload
            "data" => $data
        );
        $this->jwt = JWT::encode($this->token, $this->jwt_secret);
        return $this->jwt;
    }

    protected function _errMsg($msg)
    {
        return [
            "auth" => 0,
            "message" => $msg
        ];
    }

    //decoding the token

    public function _jwt_decode_data($jwt_token)
    {
        try {
            $decode = JWT::decode($jwt_token, $this->jwt_secret, array('HS256'));
            return [
                "auth" => 1,
                "data" => $decode->data
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->_errMsg($e->getMessage());
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return $this->_errMsg($e->getMessage());
        } catch (\Firebase\JWT\BeforeValidException $e) {
            return $this->_errMsg($e->getMessage());
        } catch (\DomainException $e) {
            return $this->_errMsg($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->_errMsg($e->getMessage());
        } catch (\UnexpectedValueException $e) {
            return $this->_errMsg($e->getMessage());
        }
    }
}
