<?php
    session_start();
    require '../core/Database.php';
    require '../core/Router.php';
    require '../core/Auth.php';
    require '../core/Ajax.php';
    require '../core/View.php';
    require '../core/Model.php';
    /* MODELS */
    require '../model/User.php';
    require '../model/Card.php';
    require '../model/Stamp.php';

    $router = Router::getInstance();
    $auth = Auth::getInstance();
    if ($confArray = json_decode(file_get_contents('../conf.json'), true)) {
        Database::getInstance()->init($confArray['db']);
        $auth->init();
        $router->route($confArray['base_url']);
    } else {

    }

?>
