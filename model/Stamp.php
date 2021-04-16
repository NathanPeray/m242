<?php
    class Stamp extends Model {

        public $id;
        public $starttime;
        public $endtime;
        public $card_FK;

        public function __construct($data = null) {
            parent::__construct($data);
        }
    }
