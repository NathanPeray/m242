<?php
    class User extends Model {

        public $id;
        public $email;
        public $prename;
        public $lastname;
        public $hash;
        public $salt;

        private $invalidFields = [];

        public function __construct($data = null) {
            parent::__construct($data);
        }

        function raw($id = "", $email, $prename, $lastname, $hash = "", $salt = "") {
            $this->id = $id;
            $this->email = $email;
            $this->prename = $prename;
            $this->lastname = $lastname;
            $this->hash = $hash == "" ? self::hexString(128) : $hash;
            $this->salt = $salt == "" ? self::hexString(32) : $salt;
        }

        function verify() {
            $status = true;
            $status = $this->verifyString($this->prename)   ? true : stroreError("prename") && $status;
            $status = $this->verifyString($this->lastname)  ? true : stroreError("lastname") && $status;
            $status = $this->verifyEmail($this->email)      ? true : stroreError("email") && $status;
            if ($status) {
                return true;
            } else {
                return false;
            }
        }
        function storeError($fieldName) {
            array_push($this->invalidField, $fieldName);
            return false;
        }


        static function hexString($bytes) {
            return bin2hex(openssl_random_pseudo_bytes($bytes / 2));
        }

        static function hashPW($pw, $salt) {
            global $confArray;
            return hash('sha512', $confArray['pepper'] . $pw . $salt);
        }
    }
?>
