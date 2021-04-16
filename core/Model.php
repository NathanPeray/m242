<?php
    class Model {

        private static $stmt;

        public function __construct($data) {
            if (!isset($data['id'])) {
                $this->fromAssoc($data, false);
                $this->id = $this->insert($this);
            } else {
                $this->fromAssoc($data);
            }
        }
        function save() {
            return Database::getInstance()->update($this);
        }
        function update() {
            return Database::getInstance()->updateModel($this);
        }
        static function get($id = null) {
            $tmp = Database::getInstance()->select(get_called_class(), $id);
            return sizeof($tmp) == 0 ? false : $id ? $tmp[0] : $tmp;
        }
        static function getAll() {
            return Database::getInstance()->select(get_called_class());
        }
        static function getFromUser() {
            return Database::getInstance()->selectFromUser(get_called_class(), Auth::getInstance()->getUser()->id);
        }
        static function getFromAuth($id = null) {
            return Database::getInstance()->selectProtected(get_called_class(), $id);
        }
        static function where($conditions) {
            $tmp = Database::getInstance()->select(get_called_class(), null,  $conditions);
            return sizeof($tmp) == 0 ? [] : $tmp;
        }
        static function like($column, $value) {
            $tmp = Database::getInstance()->like(get_called_class(), $column, $value);
            return sizeof($tmp) == 0 ? [] : $tmp;
        }
        private function insert($model) {
            $modelName = get_called_class();
            if ($model instanceof $modelName) {
                $tmp = Database::getInstance()->insert($modelName, $model);
                return $tmp;
            } else {
                echo "INVALID MODEL";
            }
        }
        function delete() {
            return Database::getInstance()->delete(get_called_class(), $this->id);
        }
        function fromAssoc($assoc, $getForeign = true) {
            if ($assoc) {
                foreach ($assoc as $key => $attribute) {
                    if (strpos($key, "_FK") && $getForeign && $attribute) {
                        $model = ucfirst(explode("_", $key)[0]);
                        $tmp =  $model::get($attribute);
                        $this->{strtolower($model)} = $tmp;
                    } else {
                        $this->{$key} = $attribute;
                    }
                }
            }
        }
        function getForeign($child) {
            $this->{strtolower($child) . "s"} = Database::getInstance()->selectForeign(get_called_class(), $child, $this->id);
        }
        function getForeignUnique($child) {
            $this->{strtolower($child) . "s"} = Database::getInstance()->selectForeignUnique(get_called_class(), $child, $this->id);
        }
        function getProperties($getId = true) {
            if ($getId) {
                return get_object_vars($this);
            } else {
                $vars = get_object_vars($this);
                return array_splice($vars, 1);
            }
        }
        function filterProperties() {
            $props = $this->getProperties();
            foreach ($props as $key => $value) {
                if(is_object($value)) {
                    $this->{$key . "_FK"} = $value->id;
                    unset($this->{$key});
                }
                unset($this->colors);
                unset($this->fits);
                unset($this->materials);
                unset($this->brand);
                unset($this->Category);
                unset($this->noshitishere);
            }
            return $this->getProperties(false);
        }
        public function __toString() {
            return $this->id;
        }
    }
?>
