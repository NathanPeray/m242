<?php
    class AppController {
        function __construct() {
            global $confArray;
        }
        function indexAction() {
            if (Auth::getInstance()->auth()) {
                new View("admin.index", "M242 | Admin", [
                    'user' => Auth::getInstance()->getUser(),
                    'stamps' => Stamp::getAll()
                ]);
            } else {
                new View("guest.index");
            }
        }
        function registerAction() {
            new View("guest.register");
        }
    }
?>
