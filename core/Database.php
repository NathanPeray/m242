<?php
    class Database {

        private $mysqli;

        public function init($data) {

            $this->mysqli = new mysqli($data['host'], $data['user'], $data['pw'], $data['db']);
            if ($this->mysqli -> connect_error) {
                // TODO: PROPPER ERROR
            }
            if (!$this->mysqli -> set_charset("utf8")) {
                // TODO: PROPPER ERROR
            }

        }
        public function select($modelName, $id = null, $where = null) {
            $models = [];
            $query = "SELECT * FROM " . strtolower($modelName);
            $query .= $id ? " WHERE id = ?" : "";
            $conditions = [];
            $conditionValues = [];
            if ($id && $where) {
                foreach($where as $condition) {
                    $query .= " AND " . $condition[0];
                    if ($condition[1] == null) {
                        $query .= " IS NULL";
                    } else {
                        $query .= " = ?";
                        array_push($conditionValues, $condition[1]);
                    }

                }
            } else if ($where) {

                foreach ($where as $condition) {
                    if ($condition[1] == null) {
                        array_push($conditions, " " . $condition[0] . " IS NULL ");
                    } else {
                        array_push($conditions, " " . $condition[0] . " = ? ");
                        array_push($conditionValues, $condition[1]);
                    }

                }
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $stmt = $this->mysqli->prepare($query);
            $types = "";
            if ($id) {
                array_unshift($conditionValues, $id);
                $types .= 'i';
            }
            if ($where) {
                for($i = 0; $i < sizeof($conditionValues); $i++) {
                    $types .= intval($conditionValues[$i]) ? 'i' : 's';
                }
            }
            if ($id | $where) {
                $stmt->bind_param($types, ...array_values($conditionValues));
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $modelName($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function selectFromUser($modelName, $userID) {
            $models = [];
            $query = "SELECT * FROM " . strtolower($modelName) . " WHERE user_FK = ?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('i', $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $modelName($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function selectForeign($parent, $child, $parentID) {
            $models = [];
            $table = strtolower($parent) . "_" . strtolower($child);
            $join = strtolower($child);
            $parent = strtolower($parent);
            $query = "SELECT $join.* FROM $table INNER JOIN $join ON $table.$join" . "_FK" . " = $join.id WHERE $parent"."_FK = ?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('i', $parentID);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $child($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function selectForeignUnique($parent, $child, $parentID) {
            $models = [];
            $table = strtolower($parent) . "_" . strtolower($child);
            $join = strtolower($child);
            $parent = strtolower($parent);
            $query = "SELECT $join.* FROM $table INNER JOIN $join ON $table.$join" . "_FK" . " = $join.id WHERE $parent"."_FK = ? GROUP BY $join.id";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('i', $parentID);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $child($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function selectProtected($modelName, $id = null) {
            $models = [];
            $authID = Auth::getInstance()->getUser()->id;
            $query = "SELECT * FROM " . strtolower($modelName);
            if ($id) {
                $query .= " WHERE id = ? AND user_fk = ? ";
                $stmt = $this->mysqli->prepare($query);
                $stmt->bind_param('ii', $id, $authID);
            } else {
                $query .= " WHERE user_fk = ? ";
                $stmt = $this->mysqli->prepare($query);
                $stmt->bind_param('i', $authID);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $modelName;
                $model->fromAssoc($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function like($modelName, $field, $value) {
            $models = [];
            $query = "SELECT * FROM " . $modelName . " WHERE " . $field . " LIKE ?";
            $stmt = $this->mysqli->prepare($query);
            $value = "%" . $value . "%";
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $modelName($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function insert($modelName, $model) {
            $keys = array_keys(get_class_vars($modelName));
            $fields= array_splice($keys, 1);
            $ph = implode(", ", $fields);
            $valType = "";
            foreach ($fields as $field) {
                $ph = str_replace($field, "?", $ph);
                $valType .= "s";
            }
            $query = "INSERT INTO " . strtolower($modelName) . "(" . implode(", ", $fields) . ") VALUES ($ph)";

            $stmt = $this->mysqli->prepare($query);
            $values = array_values($model->getProperties(false));
            $stmt->bind_param($valType, ...$values);
            $stmt->execute();
            $stmt->close();
            return $this->mysqli->insert_id;
        }
        public function update($model) {
            $table = strtolower(get_class($model));
            $query = "UPDATE $table SET ";
            $props = $model->getProperties(false);
            $attributes = [];
            $types = "";
            foreach ($props as $prop => $val) {
                array_push($attributes, "$prop = ?");
                $types .= is_int(is_object($val) ? $val->id : $val) ? 'i' : 's';
            }
            $query .= implode(", ", $attributes);
            $query .= " WHERE user_fk = " . Auth::getInstance()->getUser()->id;
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param($types, ...array_values($props));
            $stmt->execute();
            $stmt->close();
        }
        public function updateModel($model) {
            $table = strtolower(get_class($model));
            $query = "UPDATE $table SET ";
            $props = $model->filterProperties(false);
            $attributes = [];
            $types = "";
            foreach ($props as $prop => $val) {
                array_push($attributes, "$prop = ?");
                $types .= is_int(is_object($val) ? $val->id : $val) ? 'i' : 's';
            }
            $query .= implode(", ", $attributes);
            $query .= " WHERE id = " . $model->id;
            $stmt = $this->mysqli->prepare($query);
            if (!$stmt) {
                echo $this->mysqli->error;
            }
            $stmt->bind_param($types, ...array_values($props));
            $stmt->execute();
            $stmt->close();
        }
        public function delete($modelName, $id) {
            $query = "DELETE FROM " .  strtolower($modelName) . " WHERE id = ?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            echo $stmt->error;
            return true;
        }
        public function getAuthString($userid) {
            global $confArray;
            $query = "SELECT hash, salt FROM user WHERE id = ?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('i', $userid);
            $stmt->execute();
            $stmt->store_result();
            $stmt->fetch();
            $stmt->bind_result($hash, $salt);
            $stmt->close();
            return hash('sha512', $confArray['pepper'] . $hash . $salt . $_SERVER['HTTP_USER_AGENT']);

        }
        public function verifyUser($email, $hash) {
            global $confArray;
            $query = "SELECT * FROM user WHERE email = ?";
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user;
            if($row = $result->fetch_assoc()) {
                $user = new User($row);
            } else {
                $stmt->close();
                return false;
            }
            $dbHash = User::hashPW($hash , $user->salt);
            if ($dbHash == $user->hash) {
                return $user;
                $stmt->close();
            }
            return false;
        }
        public function procedure($procedure, $data) {
            if (!is_array($data)) {
                return false;
            } else {
                $params = [];
                $types = "";
                $values = [];
                foreach($data as $d) {
                    array_push($params, "?");
                    $types .= is_int($d) ? 'i' : 's';
                }
                $query = "CALL " . $procedure . "(" . implode(", ", $params) . ")";
                $stmt = $this->mysqli->prepare($query);
                if (sizeof($data) == 1) {
                    $stmt->bind_param($types, $data[0]);
                } else {
                    $stmt->bind_param($types, ...$data);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $rows = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        array_push($rows, $row);
                    }
                }
                $stmt->close();
                return $rows;
            }
        }
        public function query($query, $modelName) {
            $models = [];
            $stmt = $this->mysqli->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                $model = new $modelName;
                $model->fromAssoc($row);
                array_push($models, $model);
            }
            $stmt->close();
            return $models;
        }
        public function deleteM2M($fieldList, $table) {
            $keys = [];
            $values = [];
            $types = "";
            foreach ($fieldList as $key => $value) {
                array_push($keys, $key);
                array_push($values, $value);
                $types .= is_int(intval($value)) ? 'i' : 's';
            }
            $where = " WHERE ";
            $where .= $keys[0] . " = ?";
            for ($i = 1; $i < sizeof($keys); $i++) {
                $where .= " AND " . $key . " = ?";
            }
            $query = "DELETE FROM " . $table . " " . $where;
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
        private function deleteDressAttributes($table, $dressid) {
            $query = "";
        }
        /* Singleton */
        private static $inst = null;
        private function __construct() {}
        public static function getInstance() {
            if (null === self::$inst) {
                self::$inst = new self;
            }
            return self::$inst;
        }
    }
?>
