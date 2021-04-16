<?php
class AuthController {
    function loginAction() {
        global $confArray;
        Auth::getInstance()->verifyUser($_POST['email'], $_POST['password']);
        header("Location: http://" . $confArray['base_url']);
    }
    function registerAction() {
        global $confArray;
        $_POST['salt'] = User::hexString(16);
        $_POST['hash'] = User::hashPW($_POST['password'], $_POST['salt']);
        unset($_POST['password']);
        $user = new User($_POST);
        header("Location: http://" . $confArray['base_url']);
    }
    function logoutAction() {
        global $confArray;
        unset($_SESSION['user_id']);
        unset($_SESSION['auth']);
        Auth::getInstance()->logout();
        header("Location: http://" . $confArray['base_url']);
    }
}
?>
