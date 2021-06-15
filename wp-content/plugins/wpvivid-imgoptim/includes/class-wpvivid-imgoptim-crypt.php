<?php

if (!defined('WPVIVID_IMGOPTIM_DIR')){
    die;
}

class WPvivid_Image_Optimize_Crypt
{
    private $public_key;
    private $sym_key;

    private $rij;
    private $rsa;

    public function __construct($public_key)
    {
        $this->public_key=$public_key;
        if(!class_exists('Crypt_Rijndael'))
            include_once WPVIVID_IMGOPTIM_DIR . '/includes/Crypt/Rijndael.php';

        if(!class_exists('Crypt_RSA'))
            include_once WPVIVID_IMGOPTIM_DIR . '/includes/Crypt/RSA.php';

        if(!class_exists('Math_BigInteger'))
            include_once WPVIVID_IMGOPTIM_DIR . '/includes/Math/BigInteger.php';

        $this->rij= new Crypt_Rijndael();
        $this->rsa= new Crypt_RSA();
    }

    public function generate_key()
    {
        $this->sym_key = crypt_random_string(32);
        $this->rij->setKey($this->sym_key);
    }

    public function encrypt_message($message)
    {
        $this->generate_key();
        $key=$this->encrypt_key();
        $len=str_pad(dechex(strlen($key)),3,'0', STR_PAD_LEFT);
        $message=$this->rij->encrypt($message);
        if($message===false)
            return false;
        $message_len = str_pad(dechex(strlen($message)), 16, '0', STR_PAD_LEFT);
        return $len.$key.$message_len.$message;
    }

    public function encrypt_key()
    {
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($this->sym_key);
    }

    public function decrypt_message($message)
    {
        $len = substr($message, 0, 3);
        $len = hexdec($len);
        $key = substr($message, 3, $len);

        $cipherlen = substr($message, ($len + 3), 16);
        $cipherlen = hexdec($cipherlen);

        $data = substr($message, ($len + 19), $cipherlen);
        $rsa = new Crypt_RSA();
        $rsa->loadKey($this->public_key);
        $key=$rsa->decrypt($key);
        $rij = new Crypt_Rijndael();
        $rij->setKey($key);
        return $rij->decrypt($data);
    }

    public function encrypt_user_token($token)
    {
        $user_info['token']=$token;
        $info=json_encode($user_info);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->encrypt($info);
    }
}
