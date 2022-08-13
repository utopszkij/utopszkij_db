<?php
/**
 * Adat szerkezet
 *                                        +-------+
 *                                        | root  |
 *                                        +---+---+
 *                                            | 
 *                      +--------------------------------------------------+
 *                      |             collectionHeader Btree               |
 *                      |             indexes->id   indexes->fieldName ... |
 *                      +--------------------------------------------------+
 *                                           |                   | ...
 *                   +--------------+  +-------------+    +--------------+
 *                   | dataDocuments|  |indexDocument|    |indexDocument |
 *                   |              |  |   Btree     |    |    Btree     |
 *                   +--------------+  +-------------+    +--------------+ 
 * CollectionHeader {value:"collection_collectionName" data:{dataRoot, indexes:{indexName:indexRoot->id, ... }}}
 * DataDocument  {value: random, data: document}  (id nem mindig van a Document-ben, ill nem mindig jó a tartalma)
 * IndexDocument {value: indexName, data:dataDocument->id}
 *                             
 */
include_once 'config.php';
include_once 'btree.php';
include_once 'where.php';

global $workers, $storeError;

class Document{

}

/**
 * Új php szál, az adott collection és $id indexeinek ellenörzése, javítása
*/
function indexesRepair(string $collectionName, string $id) {


echo 'indexRepair ',$collectionName; exit();

    $rec = BtreeNode::read($id);
    $headerChange = false;
    if ($rec) {
        $collection = new Collection($this->collectionName);
        if ($collection->header->id != '') {
            foreach ($this->indexes as $indexName => $indexRootNode) {
                if (isset($rec->indexName)) {
                    $recs = $collection->getByFilter($indexName, $rec->indexName, $rec->indexName);
                    // benne van az $id?
                    $ok = false;
                    foreach ($recs as $rec) {
                        if ($rec->id == $id) {
                            $ok = true;
                        }
                    }
                    // hanincs akkor indexet kell kreálni
                    if (!$ok) {
                        $value = $rec->indexName;
                        if ($indexRootNode != '') {
                            $indexRootNode->insertChild($value, $id);
                        } else {
                            $indexNode = new BtreeNode($value,$id);
                            $indexNode->saveToDB();
                            $this->indexes->$indexFieldName = $indexNode;
                            $this->header->data->indexes->$indexFieldName = $indexNode->id;
                            $headerChange = true;
                        }
                    }
                }    
            }
            if ($headerChange) {
                $this->header->saveToDB();
            }    
        }
    }
}  


 /**
  * array to Document
  * @param array $recArray
  * @return Document
  */
 function Doc(array $recArray): Document {
    $result = new Document();
    foreach ($recArray as $fn => $fv) {
        $result->$fn = $fv;
    }
    return $result;
 }

 /**
 * ezt a ruint új adatbázis létrehozáskor kell egyetlen egyszer futtatni
 * a kapott stringet a configba ROOT_ID -nek kell beirni
 */
 function createDBROOT(): string {
    $newNode = new BtreeNode('root','root');
    $newNode->saveToDB();
    return $newNode->id;
 }

 class Collection extends Where {
    public string $collectionName;  // kollekció név
    protected $header; //  {id, ... data:{dataRoot, indexes}}
    protected $dataRoot; 
    protected $indexes;  // [fieldname => btreeNode]
    public string $error;
    protected int $offset;
    protected int $limit;
    protected array $select;
    protected string $alias;
    // inherited
    // protected array $extensions
    // addWhere($fieldName, $rel, $value)
    // addOrWhere($fieldName, $rel, $value)
    // $recs = doWhere($recs)
    
    /**
     * Ha már létezik a tábla nyilvántartásban akkor beolvassa onnan
     * @param string $source név
     * @param string $alias
     */
    function __construct(string $collectionName) {
        parent::__construct();
        $this->collectionName = $collectionName;
        $this->offset = 0;
        $this->limit = -1;
        $this->select = [];
        $this->indexes = new \stdClass();
        $this->dataRoot = '';
        $this->alias = '';
        $root = BtreeNode::readFromDB(ROOT_ID);
        $this->header = $root->find('collection_'.$collectionName);
        if ($this->header->id != '') {
            $this->indexes = clone $this->header->data->indexes;
            // most az $indexes->$indexName -ban ID van, ezek alapján
            // be kell olvasni a BtreeNode -okat
            foreach ($this->indexes as $indexName => $indexID) {
                $this->indexes->$indexName = BtreeNode::readFromDB($indexID);
            }
            // $this->dataRoot = BtreeNode::readFromDB($this->header->data->dataRoot);
        }
    }

    /**
     * string kodolás magyar ABC szerinti indexeléshez
     * egyenlőre idő és gas kimélésből belenyugszunk, hogy az index szerinti keresés
     * nem nagyar ABC sorrendet hoz ki, a Query sortBy oldja ezt meg
     */
    function hun(string $a): string {
        /*
        $Hchr = array('á'=>'az', 'é'=>'ez', 'í'=>'iz', 'ó'=>'oz', 'ö'=>'ozz', 'ő'=>'ozz', 'ú'=>'uz', 'ü'=>'uzz', 'ű'=>'uzz', 'cs'=>'cz', 'zs'=>'zz', 
        'ccs'=>'czcz', 'ggy'=>'gzgz', 'lly'=>'lzlz', 'nny'=>'nznz', 'ssz'=>'szsz', 'tty'=>'tztz', 'zzs'=>'zzzz', 'Á'=>'az', 'É'=>'ez', 'Í'=>'iz', 
        'Ó'=>'oz', 'Ö'=>'ozz', 'Ő'=>'ozz', 'Ú'=>'uz', 'Ü'=>'uzz', 'Ű'=>'uzz', 'CS'=>'cz', 'ZZ'=>'zz', 'CCS'=>'czcz', 'GGY'=>'gzgz', 'LLY'=>'lzlz', 
        'NNY'=>'nznz', 'SSZ'=>'szsz', 'TTY'=>'tztz', 'ZZS'=>'zzzz');  
        $s = strtolower(strtr($a,$Hchr));
        */
        return $a;
    }  

    /**
     * CollectionRoot létrehozása a storage -ban
     * @param array $indexNames ['fieldname',...]
     */
    public function create(array $indexNames) {
        global $items, $storeError;
        $root = BtreeNode::readFromDB(ROOT_ID);
        $header = $root->find('collection_'.$this->collectionName);
        if ($header->id == '') {
            if (!in_array('id',$indexNames)) {
                $indexNames[] = 'id';
            }
            $this->indexes = new \stdClass();
            $this->dataRoot = '';
            $headerData = new \stdClass();
            $headerData->indexes = new \stdClass();
            $headerData->dataRoot = '';
            foreach ($indexNames as $indexName) {
                $this->indexes->$indexName = '';
                $headerData->indexes->$indexName = '';
            }
            $this->header = $root->insertChild('collection_'.$this->collectionName, $headerData);
            $this->error = $storeError;
        } else {
            // már létezik
            $this->error = 'COLLECTION_EXISTS';
        }
    }

    /**
     * index item logikai törlése
     */
    protected function delIndexItem($indexRoot, $value, $data) {       
        if (is_object($indexRoot)) {                 
            $indexNode = $indexRoot->find($this->hun($value));
            while (($indexNode->id != '') & ($indexNode->value == $this->hun($value))) {
                if ($indexNode->data == $data) {
                    $indexNode->data = '';
                    $indexNode->saveToDB();
                }    
                $indexNode = $indexNode->getNext();
            }
        }
    }                        

    /**
    * Új indexItem felvitele
    * @param string | BtreeNode $indexRoot
    * @param string $value
    * @param string $data (id)
    * @param string $indexFieldName
    * @return bool headar change?
    */
    protected function addIndexItem(&$indexRoot, $value, $data, $indexFieldName) {
        global $storeError;
        if ($indexRoot != '') {
            $indexRoot->insertChild($value, $data);
            if ($storeError != '') {
                $this->error = $storeError;
            }
            $result = false;
        } else {
            $indexRoot = new BtreeNode($value,$data);
            $indexRoot->saveToDB();
            if ($storeError != '') {
                $this->error = $storeError;
            }
            $this->indexes->$indexFieldName = $indexRoot;
            $this->header->data->indexes->$indexFieldName = $indexRoot->id;
            $result = true;
        }
        return $result;
    }

    /**
     * collection header tárolása (akkor kell ha változott)
     */
    protected function saveHeader() {
        $storeError = '';
        $this->header->saveToDB();
        if ($storeError != '') {
            // hiba volt a header modosítása közben, próbáljuk újra
            $i = 0;
            while (($i < 10) & ($storeError != '')) {
                $storeError = '';
                $this->header->saveToDB();
                $i++;
            }    
            if ($storeError != '') {
                echo 'Fatális hiba a collection header tárolása közben '.$this->collectionName.' '.$storeError; exit();
            }
        }
    }


    /**
     * Új document kiirása
     * @param array $document
     * @return string Új ID

     */
    public function insert(Document $document): string {
        global $storeError;
        $this->error = '';
        if ($this->header->id != '') {
            $headerChange = false;
            $document->deleted = false;
            $storeError = '';
            $newId = BtreeNode::write($document);
            if ($storeError != '') {
                $this->error = $storeError;
                return '';
            }
            $document->id = $newId;
            $result = $newId;
            foreach ($this->indexes as $indexFieldName => $indexRootNode) {
              if (isset($document->$indexFieldName)) {
                $value = $this->hun($document->$indexFieldName);
                $storeError = '';
                $headerChange = $this->addIndexItem($indexRootNode, $value, $newId, $indexFieldName);
               }
            }
            if ($this->error != '') {
                // hiba volt az index építés közben, az indexeket újra kell építeni
                indexesRepair($this->collectionName, $newId);
            }
            if ($headerChange) {
                $this->saveHeader();
            }    
        } else {
            $result = '';
            $this->error = 'COLLECTION NOT FOUND';
        }    
        return $result;
    }

    /**
    * Új index hozzáadása meglévő kollekcióhoz
    * ha már sok dokumentum van akkor hosszú ideig is eltarthat és
    * sok memóriát igényelhet!
    * @param string $indexName
    */
    public function createIndex(string $indexName) {
           if (isset($this->indexes->$indexName)) {
              // index már létezik
              return;
           }
           $this->indexes->$indexName = '';
           $indexRootNode = '';
           // összes dokumentum elérése
           $this->whereClear();
           $this->select(['id',$indexName]);
           $documents = $this->all();
           // index képzése mindegyik dokumentumhoz
           foreach ($documents as $document) {
                if (isset($document->$indexName)) {
                    $value = $this->hun($document->$indexName);
                    $this->addIndexItem($indexRootNode, $value, $document->id, $indexName);
                }    
            }
            $this->saveHeader();
    }

    /**
    * index törlése
    * valójában a további indexelést és az index szerinti keresést
    * állítja le, a storage -ban megmaradnak az eddigi index adatok, de
    * a továbbiakban nem lesznek használva.
    * @param string $indexName
    */
    public function dropIndex(string $indexName) {
       if ($indexName == 'id') {
          // nem törölhető
          return;
       }
       if (!isset($this->indexes->$indexName)) {
          // nincs ilyen
          return;
       }
       unset($this->indexes->$indexName);
       unset($this->header->data->indexes->$indexName);
       $this->saveHeader();
       return;
    }

    /**
     * Document modosítása
     * @param string $id
     * @param array $document
     * @return bool
     */
    public function updateById(string $id, Document $document): bool {
        global $storeError;
        $this->error = '';
        if ($this->header->id != '') {
            // oldrec beolvasása
            $oldRec = BtreeNode::read($id);
            if ($oldRec) {
                $oldRec->id = $id;
                if ($oldRec->deleted == false) {
                    $document->id = $id;
                    $newRec = $document;
                    $newRec->id = $id;
                    $newRec->deleted = $oldRec->deleted;
                    $storeError = '';
                    BtreeNode::update($id, $newRec);

                    if ($storeError != '') {
                        $this->error = $storeError;
                        return false;
                    }
                    foreach  ($this->indexes as $indexFieldName => $indexRoot)  {
                        $storeError = '';
                        if ((isset($oldRec->$indexFieldName)) && (!isset($newRec->$indexFieldName))) {
                            $this->delIndexItem($indexRoot, $oldRec->$indexFieldName, $id);
                        } else if ((!isset($oldRec->$indexFieldName)) && (isset($newRec->$indexFieldName))) {
                            $value = $this->hun($document->$indexFieldName);
                            $this->addIndexItem($indexRoot, $value, $dataNode->id, $indexFieldName);
                        } else if ((!isset($oldRec->$indexFieldName)) && (!isset($newRec->$indexFieldName))) {
                            ; // nincs teendő
                        } else if ($oldRec->$indexFieldName != $newRec->$indexFieldName) {
                            $this->delIndexItem($indexRoot, $oldRec->$indexFieldName, $id);
                            $value = $this->hun($document->$indexFieldName);
                            $this->addIndexItem( $indexRoot, $value, $document->id, $indexFieldName );
                        }
                        if ($this->error != '') {
                            // hiba volt valamelyik index modosítása közben, az indexeket újra kell építeni!
                            indexesRepair($this->collectionName, $id);
                        }
                    }
                    $this->saveHeader();
                    $result = true;
                } else {    
                    $result = false;
                    $this->error = 'DELETED';
                }
            } else {
                $result = false;
                $this->error = 'NOT FOUND';
            }
        } else {
            $result = false;
            $this->error = 'COLLECTION NOT FOUND';
        }
        return $result;
    }

    /**
     * Document (logikai) törlése
     * @param string $id
     * @return bool
     */
    public function deleteById(string $id): bool {
        $result = false;
        if ($this->header->id != '') {
            // oldrec beolvasása
            $oldRec = BtreeNode::read($id);
            $oldRec->id = $id;
            if ($oldRec) {
                foreach  ($this->indexes as $indexFieldName => $indexRoot) {
                    if (isset($oldRec->$indexFieldName)) {
                        $this->delIndexItem($indexRoot, $oldRec->$indexFieldName, $id);
                    }    
                }
                $oldRec = new \stdClass();
                $oldRec->id = $id;
                $oldRec->deleted = true;
                BtreeNode::update($id, $oldRec);
                $result = true;
            } else {
                $result = false;
                $this->error = 'NOT FOUND';
            }
        } else {
            $result = false;
            $this->error = 'COLLECTION NOT FOUND';
        }   
        $this->expression = [];
        return $result;
    }    

    /**
     * Document elérés id alapján
     * @param string $id
     * @return object $document , ha nincs ilyen akkor {}
     */
    public function getById(string $id) {
        $result = new \stdClass();
        if ($this->header->id != '') {
            $result = BtreeNode::read($id);
            if ($result) {
                $result->id = $id; 
                if ($result->deleted) {
                    $result = new \stdClass();
                    $this->error = 'NOT FOUND';
                }
            } else {
                $result = new \stdClass();
                $this->error = 'NOT FOUND';
            }    
        } else {
            $this->error = 'COLLECTION NOT FOUND';
        }    
        return $result;        
    }

    /**
     * dokumentumok olvasása
     * ha a megadott indexFieldname -hez nincs index az id szerinti soros keresés
     * @param string $indexFieldName  haszálandó index
     * @param string $minValue        min.value az indexen
     * @param string $maxValue        max.value az indexen lehet 'none' is
     * @param function(array $rec): bool filterFun
     * @return array [{fieldname:value,...},...]
     */
    public function getByFilter(string $indexFieldName,
        string $minValue,
        string $maxValue) {
        global $workers;    
        $minValue =  $this->hun($minValue);   
        $maxValue =  $this->hun($maxValue);   
        $result = [];
        if ($this->header->id != '') {
            if (!isset($this->indexes->$indexFieldName)) {
                  $indexFieldName = 'id';
                  $minValue = '';
                  $maxValue = 'none';
            }
            $indexRoot = $this->indexes->$indexFieldName;
            if ($indexRoot == '') {
                return [];
            }
            if (($minValue == '') & ($maxValue == 'none')) {
                // Egyszerüsített keresés BtreeNode->all használatával
                // minValue, maxValue és inde x éppség elenörzés nélkül
                $ids = $indexRoot->all();
                foreach ($ids as $id) {
                    if ($id != '') { 
                        $rec = BtreeNode::read($id);
                        if ($rec) {
                            $rec->id = $id;
                            if ($rec->deleted == false) {
                                if ($this->whereCheck($rec)) {
                                    $result[] = $rec;
                                }    
                            }
                        }    
                    }
                }
            } else {
                // keresés index szerint minvalue, maxValue használatával,
                // index éppség ellenörzéssel
                $node = $indexRoot->find($minValue);
                if ($node->id == '') {
                    $node = $indexRoot->getFirstChild();
                } else {
                    while (($node->id != '') & ($node->value == $minValue)) {
                        $node = $node->getPrevious();
                    }
                    if ($node->id == '') {
                        $node = $indexRoot->getFirstChild();
                    }
                }   
                if ($node->id == '') {
                    $node = $indexRoot;
                }
                while (($node->id != '') &
                    (($node->value <= $maxValue) | ($maxValue == 'none'))) {
                    if (($node->data != '') & 
                        ($node->value != 'key_'.$this->collectionName.'_'.$indexFieldName) &
                        ($node->value >= $minValue)) {
                        $rec = BtreeNode::read($node->data);
                        if ($rec) {
                            $rec->id = $node->data;
                            if ($rec->deleted == false) {
                                if ($rec->$indexFieldName == $node->value) {
                                    if ($this->whereCheck($rec)) {
                                        $result[] = $rec;
                                    }
                                } else {
                                    // sérült index btree !
                                    $node->data = '';
                                    $node->saveToDB();
                                    // $rec->id indexeit ellnörizni, újra építeni kell.
                                    indexesRepair($this->collectionName, $rec->id);
                                }        
                            }
                        }    
                    }
                    $node = $node->getNext();
                }
            } // egyszerüsített vagy normál keresés
        } else {
            $this->error = 'COLLECTION NOT FOUND';
        }    
        $this->expression = [];
        return $result;
    } 
    
    /**
     * document array -ból a select alapján document objectet alakít ki.
     * @param object $rec
     * @param string $prefix  a resultban az oszlopnevek elé kerül
     * @return Document
     */
    public function buildResultRow($rec, string $prefix=''): Document {
        $result = new Document();
        foreach ($rec as $fn => $fv) {
            $colName = $prefix.$fn;
            if (count($this->select) == 0) {
                if (($fn != '_orderBy') & ($fn != 'deleted')) {
                    $result->$colName = $fv;
                }    
            } else {
                if (isset($this->select[$fn])) {
                    $result->$colName = $fv;
                }
            }
        }
        return $result;
    }
  
    /**
     * Offset deiniálás
     * @param int $offset
     * @return Collection
     */
    public function offset(int $offset): Collection {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Limit deiniálás
     * @param int $limit
     * @return Table
     */
    public function limit(int $limit): Collection {
        $this->limit = $limit;
        return $this;
    }

    /**
     * és -el összekapcsolt where feltétel hozzáadása az aktuális csoporthoz
     * @param string $fieldName
     * @param string $rel '<'|'<=','=','<>','>','>='
     * @param $value  szám, string, bool konstans vagy '`fieldName' lehet
     */
    public function where($fieldName, $rel, $value): Collection {
        $this->addWhere($fieldName, $rel, $value);
        return $this;
    }

    /**
     * or-al kapcsolt where csoprt kezdése hozzáadása
     * @param string $fieldName
     * @param string $rel '<'|'<=','=','<>','>','>='
     * @param $value  szám, string, bool konstans vagy '`fieldName' lehet
     */
    public function orWhere($fieldName, $rel, $value): Collection {
        $this->addOrWhere($fieldName, $rel, $value);
        return $this;
    }

    /**
     * select definiálása
     * a [funName, fieldName, alias] forma csak Query -ben, gropBy -al használhatóa 
     * funName: COUNT | SUM | MIN | MAX | AVG 
     * @param array $fieldnames ['fieldName'...., [funname,'fieldname','alias'],....]
     */
    public function select(array $fieldNames): Collection {
        $this->select = [];
        foreach ($fieldNames as $fieldName) {
            if (is_array($fieldName)) {
                $this->select[fieldname[1]] = $fieldName[2];
            } else {
                $this->select[$fieldName] = '';
            }
        }
        return $this;
    }

    /**
     * dokumentumok olvasása a bállított were, select, orderBy, offset, limit paraméterek alapján
     * @param string $prefix az outputban a mező nevek elé kerül
     * @return array [Document, Document,...]
     */
    public function all(string $prefix = '') {
        if (($prefix == '') & ($this->alias != '')) {
            $prefix = $this->alias.'.';
        }
        // használandó index és min, max megállapítása a where alapján
        $keyName = 'id';
        $minValue = '';
        $maxValue = 'none';
        if (count($this->expression) == 1) {
            if (is_array($this->expression[0])) {
                if (isset($this->expression[0][0])) {
                    $condition = $this->expression[0][0];
                    $keyName = $condition[0];
                    if (($condition[1] == '<') | ($condition[1] == '<=') | ($condition[1] == '=')) {
                        $maxValue = $condition[2];
                    }
                    if (($condition[1] == '>') | ($condition[1] == '>=') | ($condition[1] == '=')) {
                        $minValue = $condition[2];
                    }
                }    
                if (isset($this->expression[0][1])) {
                    $condition = $this->expression[0][1];
                    if ($condition[0] == $keyName) {
                        if (($condition[1] == '<') | ($condition[1] == '<=') | ($condition[1] == '=')) {
                            $maxValue = $condition[2];
                        }
                        if (($condition[1] == '>') | ($condition[1] == '>=') | ($condition[1] == '=')) {
                            $minValue = $condition[2];
                        }
                    }
                }
            } 
        }
        // dokumentum lekérés a where -el filterezve
        if (($keyName == 'id') & ($minValue == $maxValue)) {
            $recs = [$this->getById($minValue)];    
        } else {
            $recs = $this->getByFilter($keyName, $minValue, $maxValue);
        }
        // result kialakítása
        $result = [];
        $count = 0;
        for ($i=0; $i < count($recs); $i++) {
            if (($i >= $this->offset) & 
                (($count <= $this->limit) | ($this->limit < 0))) {
                $result[] = $this->buildResultRow($recs[$i],$prefix);    
                $count++;
            }
        }
        $this->expression = [];
        return $result;
    }
 }

?>