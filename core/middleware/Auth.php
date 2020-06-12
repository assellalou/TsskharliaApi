<?php



class Auth extends JwtHandler
{
    protected $db;
    protected $headers;
    protected $token;
    public function __construct($db, $headers)
    {
        parent::__construct();
        $this->db = $db;
        $this->headers = $headers;
    }

    public function isAuth()
    {
        if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) :
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if (isset($this->token[1]) && !empty(trim($this->token[1]))) :
                $data = $this->_jwt_decode_data($this->token[1]);

                if (isset($data['auth']) && isset($data['data']->user_id) && $data['auth']) :

                    return $data['data']->user_id;
                else :
                    return null;
                endif;
            else :
                return null;
            endif;
        else :
            return null;
        endif;
    }
}
