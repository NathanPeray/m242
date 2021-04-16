<?php
    class Auth {

        private $user;

        private $loggedIn = false;

        function auth() {
            return $this->loggedIn;
        }
        function getUser() {
            return $this->user;
        }
        function getUserName() {
            return $this->user->prename . " " . $this->user->lastname;
        }
        public function init() {
            if (isset($_SESSION['user_id'], $_SESSION['auth'])) {
                $this->loggedIn = $this->verifyLoginState($_SESSION['auth']);
                if ($this->loggedIn) {
                    $this->user = User::get($_SESSION['user_id']);
                }
            }
        }
        public function logout() {
            unset($this->user);
            $this->loggedIn = false;
        }
        private function verifyLoginState($userid) {
            $clientString = $_SESSION['auth'];
            $dbString = $this->DB->getAuthString($userid);
            return $clientString == $dbString ? true : false;
        }
        public function verifyUser($email, $hash) {
            global $confArray;
            if ($user = $this->DB->getInstance()->verifyUser($email, $hash)) {
                $_SESSION['auth'] = $this->DB->getAuthString($user->id);
                $_SESSION['user_id'] = $user->id;
                return true;
            } else {
                return false;
            }
        }

        /* Singleton */
        private static $inst = null;
        private function __construct() {$this->DB = Database::getInstance();}
        public static function getInstance() {
            if (null === self::$inst) {
                self::$inst = new self;
            }
            return self::$inst;
        }
    }
?>
