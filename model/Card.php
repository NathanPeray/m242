<?php
    class Card extends Model {

        public $id;
        public $uid;
        public $user_FK;

        public function __construct($data = null) {
            parent::__construct($data);
        }
    }
