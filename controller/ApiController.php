<?php
    class ApiController {
        function __construct() {
            global $confArray;
        }
        function indexAction() {
            if(!isset($_GET['uid'])) {
                echo json_encode([
                    "message" => "Error : \nInvalid UID"
                ]);
                return;
            }
            $card = Card::where([["uid", $_GET['uid']]]);
            if (sizeof($card) < 1) {
                $card = new Card(['uid' => $_GET['uid'], 'user_FK' => NULL]);
            } else {
                $card = $card[0];
            }
            $stamp = Stamp::where([["endtime", null], ["card_FK", $card->id]]);
            $now = new DateTime();
            if (sizeof($stamp) < 1) {
                $starttime = $now->format('Y-m-d H:i:s');
                $endtime = null;
                echo json_encode([
                    "message" => "Welcome \nback"
                ]);
            } else {
                $starttime = $stamp[0]->starttime;
                $stamp[0]->delete();
                $endtime =  $now->format('Y-m-d H:i:s');
                echo json_encode([
                    'message' => "Bye"
                ]);
            }
            new Stamp(['starttime' => $starttime, 'endtime' => $endtime, 'card_FK' => $card->id]);
        }
    }
?>
