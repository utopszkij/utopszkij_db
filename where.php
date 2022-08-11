<?php
/**
 * where objekt
 * 
 * Condition    feltétel [fildname, rel, value]
 * Conditions   és kapcsolatban álló feltétel sorozat [condition, ....] 
 * Expression   vagy kapcsolatban álló Conditions -ok [Conditions, ...] 
 */

class Where {
    public $expression; // [['fieldName','rel','value'],....]

    function __construct() {
        $this->expression = [];
    }

    public function whereClear() {
        $this->expression = [];
    }

    /**
     * És kapcsolatban álló feltétel sorozat feldolgozása
     * @param array $conditions [[fieldName, rel, value],....]
     * @param object $record
     * @return bool
     */
    protected function conditionsCheck($conditions, $rec):bool {
        $result = true;
        foreach ($conditions as $condition) {
            $fieldName = $condition[0];
            if (!isset($rec->$fieldName)) {
                echo ' field not found '.$fieldName; exit();
                return false;
            }
            if (substr($condition[2],0,1) == '`') {
                $valueName = substr($condition[2],1,100);
                if (!isset($rec->$valueName)) {
                    echo ' value not found '.$valueName; exit();
                    return false;
                } else {
                    $condition[2] = $rec->$valueName;
                }
            }   
            if ($condition[1] == '<') {
                $res = ($rec->$fieldName < $condition[2]);
            }
            if ($condition[1] == '<=') {
                $res = ($rec->$fieldName <= $condition[2]);
            }
            if ($condition[1] == '=') {
                $res = ($rec->$fieldName == $condition[2]);
            }
            if ($condition[1] == '>') {
                $res = ($rec->$fieldName > $condition[2]);
            }
            if ($condition[1] == '>=') {
                $res = ($rec->$fieldName >= $condition[2]);
            }
            if ($condition[1] == '<>') {
                $res = ($rec->$fieldName != $condition[2]);
            }
            if ($condition[1] == '!=') {
                $res = ($rec->$fieldName != $condition[2]);
            }
            if (!$res) {
                $result = false;
                return $result;
            }
        }
        return $result;
    }

    /**
     * where feltétel feldolgozása
     * @param object $rec
     * @return bool
     */
    protected function whereCheck($rec):bool {
        if (count($this->expression) == 0) {
            return true;
        }
        $result = false;
        foreach ($this->expression as $conditions) {
            $res = $this->conditionsCheck($conditions,$rec);
            if ($res) {
                return true;
            }
        }
        return $result;
    }

    /**
     * és el kapcsolt új feltétel hozzáadása az utolsó conditions -hoz, vagy ha még nincs
     * akkor létrehozza az első conditions -t, és ebbe írja az első condition -t
     * @param string $fieldName
     * @param string $rel <|<=|=|>|>=|<>|!=
     * @param mixed $value  string|number|bool|`fieldName
     */
    protected function addWhere(string $fieldName, string $rel, mixed $value) {
        if (count($this->expression) == 0) {
            $this->expression[] = [];
        }
        $this->expression[count($this->expression) - 1][] = [$fieldName, $rel, $value];
    }

    /**
     * új vagy al kapcsolt conditions nyitása, és ebbe az első condition beírása
     * @param string $fieldName
     * @param string $rel <|<=|=|>|>=|<>|!=
     * @param mixed $value
     */
    protected function addOrWhere(string $fieldName, string $rel, mixed $value) {
        $this->expression[] = [];
        $this->expression[count($this->expression) - 1][] = [$fieldName, $rel, $value];
    }
    
}

?>