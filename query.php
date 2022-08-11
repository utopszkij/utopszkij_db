<?php
/**
 * Query objektum 
 */

include_once 'btree.php';
include_once 'where.php';
include_once 'collection.php';

 class Query extends Where {
    protected $source; // Query or Collection
    protected $alias;
    protected $orderBy;
    protected $groupBy;
    protected $distinct; 
    protected $offset;
    protected $limit;
    public $havings; 
    protected $selects; // [fieldName => alias | _funName => [fieldName, alias], ... ]]
    // inherited
    // protected array extensions
    // addWhere($fieldName, $rel, $value)
    // addOrWhere($fieldName, $rel, $value)
    // $recs = doWhere($recs)

    protected $unions;
    protected $joins;

    function __construct($source) {
        parent::__construct();
        if (is_string($source)) {
            $this->source = new Collection($source);
        } else {
            $this->source = $source;
        }
        $this->alias = '';
        $this->orderBy = [];
        $this->groupBy = [];
        $this->distinct = false; 
        $this->offset = 0;
        $this->limit = -1;
        $this->selects = [];
        $this->unions = [$this->source];
        $this->joins = [];
        $this->havings = [];
        }

    /**
     * OrderBy deiniálás
     * @param array $orderBy ['fieldName', ...['fieldName,'DESC']...]
     * @return Query
     */
    public function orderBy(array $orderBy): Query {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Group by deiniálás
     * @param array $groupBy ['fieldName', ...]
     * @return Query
     */
    public function groupBy(array $groupBy): Query {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Distinct deiniálás
     * @return Query
     */
    public function distinct(): Query {
        $this->distinct = true;
        return $this;
    }

    /**
     * Offset deiniálás
     * @param int $offset
     * @return Query
     */
    public function offset(int $offset): Query {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Limit deiniálás
     * @param int $limit
     * @return Query
     */
    public function limit(int $limit): Query {
        $this->limit = $limit;
        return $this;
    }

    public function where($fieldName, $rel, $value): Query {
        $this->addWhere($fieldName, $rel, $value);
        return $this;
    }

    public function orWhere($fieldName, $rel, $value): Query {
        $this->addOrWhere($fieldName, $rel, $value);
        return $this;
    }

    public function having($fieldName, $rel, $value): Query {
        if (count($this->havings) == 0) {
            $this->havings[] = [];
        }
        $this->havings[count($this->havings) - 1][] = [$fieldName, $rel, $value];
        return $this;
    }

    public function orHaving($fieldName, $rel, $value): Query {
        $this->havings[] = [];
        $this->havings[count($this->havings) - 1][] = [$fieldName, $rel, $value];
    }
    

    /**
     * select definiálása
     * @param array $fieldnames ['fieldName'...., ['fieldname','alias'],...[ funName, fieldName, alias]]
     */
    public function select(array $colDefs): Query {
        $this->selects = [];
        foreach ($colDefs as $colDef) {
            if (is_string($colDef)) {
                $this->selects[$colDef] = $colDef; // mezőnév alias nélkül
            } else if (count($colDef) == 2) {
                $this->selects[$colDef[0]] = $colDef[1]; // mezőnév aliassal
            } else if (count($colDef) == 3) {
                $this->selects[$colDef[0]] = [$colDef[1], $colDef[2]]; // function aliassal
            }
        }
        return $this;
    }

    /**
     * union definiálása
     */
    public function union(Query $query): Query {
        $this->unions[] = $query;
        return $this;
    }

    /**
     * JOIN definiálása
     */
    public function join($joinType, $joinSource, $sourceField, $joinedField, $alias) {
        $this->joins[] = [$joinType, $joinSource, $sourceField, $joinedField, $alias];
        return $this;
    }

    /**
     * record array -ból a select alapján record objectet alakít ki.
     * @param object $rec
     * @param string $prefix  a resultban az oszlopnevek elé kerül
     * @return Document
     */
    public function buildResultRow($rec, string $prefix=''): Document {
        $result = new Document();
        if ($this->alias != '') {
            $prefix .= $this->alias.'.';
        }
        if (count($this->selects) == 0) {
            if (isset($rec->_orderBy)) unset($rec->_orderBy);
            foreach ($rec as $fn => $fv) {
                $alias = $prefix.$fn;
                $result->$alias = $rec->$fn;
            }
            return $result;
        }
        foreach ($this->selects as $cn => $colDef) {
            if (is_array($colDef)) {
                $colName = $colDef[0];
                $alias = $prefix.$colDef[1];
            } else {
                $colName = $colDef;
                $alias = $prefix.$colDef;
            }    
            if (isset($rec->$colName)) { 
                $result->$alias = $rec->$alias;
            } else if (isset($rec->$alias)) { 
                $result->$alias = $rec->$alias;
            } else {
                $result->$alias = '';
            }    
        }
        return $result;
    }
  
    protected function doDistinct(array $recs): array {
        $result = [];
        foreach ($recs as $rec) {
            // van már ilyen az otpuban?
            $exists = false;
            foreach ($result as $res) {
                if (JSON_encode($res) == JSON_encode($rec)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $result[] = $rec;
            }
        }
        $recs = [];
        return $result;
    }

    protected function doJoin($recs, $join): array {
        $result = [];
        $joinType = $join[0];
        $joinSource = new Collection($join[1]);
        $sourceField = $join[2]; 
        $joinedField = $join[3];
        $joinAlias = $join[4];
        foreach ($recs as $rec) {
                $joinSource->whereClear();
                $joinedRecs = $joinSource->where($joinedField,'=', $rec->$sourceField)->all($joinAlias.'.');
                if (count($joinedRecs) > 0) {
                    // rekord összefüzés, szükség esetén sokszorozás 
                    foreach ($joinedRecs as $joinedRec) {
                        foreach ($rec as $fn => $fv) {
                            $joinedRec->$fn = $fv;
                        }
                        $result[] = $joinedRec;
                    }                    
                } else if ($joinType == 'LEFT OUTER') {
                        $result[] = $rec;
                }
        }
        $recs = [];
        return  $result;
    }

    /**
     * groupBy feldolgozáshoz:  a $rec a current csoportba tartozik?
     */
    protected function checkGroup($rec, $current):bool {{
        if ($current == false) {
            return false;
        }
        $result = true;
        if (is_object($rec) & (is_object($current))) {
            foreach ($this->groupBy as $groupField) {
                if ($rec->$groupField != $current->$groupField) {
                    $result = false;
                }
            }
        }
        return $result;
    }}

    /**
     * groupBy feldolgozáshoz: current "csoport" rekord modosítása a rec rekord tartalmával
     */
    protected function processGroup($rec, &$current) {
        foreach ($this->selects as $fn => $w) {
            if (is_array($w)) {
                $fun = $fn;
                $alias = $w[1];
                $colName = $w[0];
            } else {
                $fun = '';
            }
            if ($fun == 'SUM') {
                $current->$alias = $current->$alias + $rec->$colName;
            }
            if ($fun == 'MIN') {
                if ($rec->$colName < $current->$alias) {
                    $current->$alias = $rec->$colName;
                }
            }
            if ($fun == 'MAX') {
                if ($rec->$colName > $current->$alias) {
                    $current->$alias = $rec->$colName;
                }
            }
            if ($fun == 'AVG') {
                $current->$alias = $current->$alias + $rec->$colName;
            }
        }
    }

    protected function doGroupBy(array $recs): array {
        $result = [];
        $current = false;
        for($i = 0; $i < count($recs); $i++) {
            if ($recs[$i] != 'processed') {
                // Új csoport inditása
                $groupCount = 1;
                $current = clone $recs[$i];
            }            
            for($j = $i + 1; $j < count($recs); $j++) {
                $rec = $recs[$j];
                if ($rec != 'processed') {
                    if ($this->checkGroup($rec, $current)) {
                        //   agregat funkciós mezők kezelése
                        $this->processGroup($rec, $current);
                        $recs[$j] = 'processed';
                        $groupCount++;
                    }    
                }
            }    
            // group lezárása count és AVG mezők kitöltése a current rekordban
            if ($current) {
                foreach ($this->selects as $fn => $w) {
                    if (is_array($w)) {
                        $fun = $fn;
                    } else {
                        $fun = '';
                    }
                    if ($fun == 'AVG') {
                        $alias = $w[1];
                        $current->$alias = $current->$alias / $groupCount;
                    }
                    if ($fun == 'COUNT') {
                        $alias = $w[1];
                        $current->$alias = $groupCount;
                    }
                }
                $result[] = clone $current;
                $current = false;
            }
        }
        $recs = [];
        return $result;
    }

    protected function doWhere(array $recs): array {
        $result = [];
        foreach ($recs as $rec) {
            if ($this->whereCheck($rec)) {
                $result[] = $rec;
            }
        }
        $recs = [];
        return $result;
    }


    /**
     * stringek magyar ékezetes rendezéséhez összehasonlítáa
     */
    public static function Hcmp($a, $b) {
        if (is_string($a) | is_string($b)) {
            static $Hchr = array('á'=>'az', 'é'=>'ez', 'í'=>'iz', 'ó'=>'oz', 'ö'=>'ozz', 'ő'=>'ozz', 'ú'=>'uz', 'ü'=>'uzz', 'ű'=>'uzz', 'cs'=>'cz', 'zs'=>'zz', 
            'ccs'=>'czcz', 'ggy'=>'gzgz', 'lly'=>'lzlz', 'nny'=>'nznz', 'ssz'=>'szsz', 'tty'=>'tztz', 'zzs'=>'zzzz', 'Á'=>'az', 'É'=>'ez', 'Í'=>'iz', 
            'Ó'=>'oz', 'Ö'=>'ozz', 'Ő'=>'ozz', 'Ú'=>'uz', 'Ü'=>'uzz', 'Ű'=>'uzz', 'CS'=>'cz', 'ZZ'=>'zz', 'CCS'=>'czcz', 'GGY'=>'gzgz', 'LLY'=>'lzlz', 
            'NNY'=>'nznz', 'SSZ'=>'szsz', 'TTY'=>'tztz', 'ZZS'=>'zzzz');  
            $a = strtr($a,$Hchr);   $b = strtr($b,$Hchr);
            $a=strtolower($a); $b=strtolower($b);
            return strcmp($a, $b);
        } else {
            if ($a > $b) $result = 1;
            if ($a == $b) $result = 0;
            if ($a < $b) $result = -1;
            return $result;
        }
    }  

    /**
     * rekordok olvasása a bállított were, select, orderBy, offset, limit 
     * distinct, union, join paraméterek alapján
     * @param string $prefix az outputban a mező nevek elé kerül
     */
    public function all(string $prefix = '') {
        // rekord lekérés
        if (count($this->unions) == 1) {
            $this->unions[0]->expression = $this->expression;
            $this->unions[0]->havings = $this->havings;
            $recs = $this->unions[0]->all();
        } else {
            $recs = [];
            for ($i = 1; $i < count($this->unions); $i++) {
                $union = $this->unions[$i];
                $recs1 = $union->all();
                $recs = array_merge($recs, $recs1);
            }
        }  

        // join kezelés 
        if (count($this->joins) > 0) {
            foreach ($this->joins as $join) {
                $recs = $this->doJoin($recs, $join);
            }
        }

        // where feltétel kezelés
        $recs = $this->doWhere($recs);

        // rendezés
        if (count($this->orderBy) > 0) {
            for ($i = 0; $i < count($recs); $i++) {
                $recs[$i]->_orderBy = $this->orderBy;
            }
            usort($recs, function($a,$b) {
                if (count($a->_orderBy) == 0) {
                    return 0;
                }
                foreach ($a->_orderBy as $sortDef) {
                    if (is_array($sortDef)) {
                        $sortField = $sortDef[0];
                        $sortDir = $sortDef[1];
                    } else {
                        $sortField = $sortDef;
                        $sortDir = 'ASC';
                    }
                    if ($sortDir == 'DESC') {
                        $res1 = Query::Hcmp($b->$sortField, $a->$sortField);
                    } else {
                        $res1 = Query::Hcmp($a->$sortField, $b->$sortField);
                    }
                    if ($res1 != 0) return $res1;
                }
                return 0;
            });
        }    

        // kiegészités a függvény oszlopokkal)
        for ($i=0; $i < count($recs); $i++) {
            foreach ($this->selects as $cn => $colDef) {
                if (is_array($colDef)) {
                    $alias = $colDef[1];
                    $colName = $colDef[0];
                    $recs[$i]->$alias = $recs[$i]->$colName;
                }
            }
        }

        // groupBy feldolgozás
        if (count($this->groupBy) > 0) {
            $recs = $this->doGroupBy($recs);
        }

        // having feldolgozás 
        if (count($this->havings) > 0) {
            $this->extension = $this->havings;
            $recs = $this->doWhere($recs);
        }

        // végleges record szerkezet kialakítása
        $recs2 = [];
        for ($i=0; $i < count($recs); $i++) {
            $recs2[] = $this->buildResultRow($recs[$i],$prefix);    
        }
        $recs = [];

        // distinct feldolgozás
        if ($this->distinct) {
            $recs2 = $this->doDistinct($recs2);
        }

        // offset, limit feldolgozás
        if (($this->offset > 0) | ($this->limit > 0)) {
            $result = [];
            $count = 0;
            for ($i=0; $i < count($recs2); $i++) {
                if (($i >= $this->offset) & 
                    (($count < $this->limit) | ($this->limit < 0))) {
                    $result[] = $this->buildResultRow($recs2[$i],$prefix);    
                    $count++;
                }
            }
            $recs2 = [];
        } else {
            $result = $recs2;
        }

        $this->expression = [];
        $this->havings = [];
        $this->unions = [$this->source];
        $this->joins = [];
        $this->select = [];
        $this->offset = 0;
        $this->limit = 0;
        $this->distinct = false;
        $this->orderBy = [];
        $this->groupBy = [];
        return $result;

    }

    /**
     * feltételeknek megfelelő első rekord
     * @return Record
     */
    public function first(): Record {
        $result = new Record();
        $this->offset = 0;
        $this->limit = 1;
        $recs = $this->all();
        $this->limit = 0;
        if (count($recs) > 0) {
            $result = $recs[0];
        }
        return $result;
    }
 }

?>