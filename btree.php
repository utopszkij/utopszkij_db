<?php

/**
 * Btree kezelő
 * törlés ezen a szinet nincs kmegvalósítva
 */
class BtreeNode extends Storage {
    public string $left;
    public string $right;
    public string $parent;
    public string $id;
    public string $value;
    public $data;

    function __construct(string $value, $data) {
        $this->left = '';
        $this->right = '';
        $this->parent = '';
        $this->id = '';
        $this->value = $value;
        $this->data = $data;
    }

    /**
     * node olvasás a $storgae -ból
     * @param string $id
     * @return BtreeNode
     */
    public static function readFromDB(string $id): BtreeNode {
        global $error;
        $rec = BtreeNode::read($id);
        if (($rec) && (isset($rec->value))) {
          $node = new BtreeNode($rec->value, $rec->data);
          $node->left = $rec->left;
          $node->right = $rec->right;
          $node->id = $id;
          $node->parent =$rec->parent;
        } else {
          $node = new BtreeNode('', '');
          $error = 'BTREE NODE NOT FOUND';
        }
        return $node;
    }

    /**
     * node tárolása a $storage -ba
     */
    public function saveToDB():bool {
        if ($this->id != '') {
            $this->update($this->id, [
                "id" => $this->id,
                "parent" => $this->parent,
                "left" => $this->left,
                "right" => $this->right,
                "value" => $this->value,
                "data" => $this->data
            ]);
        } else {
            $this->id = $this->write([
                "left" => $this->left,
                "right" => $this->right,
                "parent" => $this->parent,
                "value" => $this->value,
                "data" => $this->data
            ]);    
        }    
        return true;
    }

    /**
     * Új child node beillesztése 
     * @param string $value
     * @param string $data
     * @return BtreeNode
     */
    public function insertChild(string $value, $data):BtreeNode {
        if ($value <= $this->value) {
            if ($this->left == '') {
                $newNode = new BtreeNode($value, $data);
                $newNode->parent = $this->id;
                $newNode->saveToDB();
                $this->left = $newNode->id;
                $this->saveToDB();
                return $newNode;
            } else {
                $left = $this->readFromDB($this->left);
                $newNode = $left->insertChild($value, $data);
                return $newNode;
            }
        } else {
            if ($this->right == '') {
                $newNode = new BtreeNode($value, $data);
                $newNode->parent = $this->id;
                $newNode->saveToDB();
                $this->right = $newNode->id;
                $this->saveToDB();
                return $newNode;
            } else {
                $right = $this->readFromDB($this->right);
                $newNode = $right->insertChild($value, $data);
                return $newNode;
            }
        }
        return $newNode;
    }

    /**
     * legkisebb child elérése, 
     * @return BtreeNode ha nincs ilyen akkor  {id:"",....}
     */
    public function getFirstChild(): BtreeNode {
        if ($this->left == '') {
            $result = new BtreeNode('','');
        } else {
            $left = $this->readFromDB($this->left);
            $result = $left->getFirstChild();
            if ($result->id == '') {
                $result = $left;
            }
        }
        return $result;
    }

    /**
     * legnagyobb child elérése
     * @return BtreeNode  ha nincs ilyen akkor {id:"",....}
     */
    public function getLastChild() {
        if ($this->right == '') {
            $result = new BtreeNode('','');
        } else {
            $right = $this->readFromDB($this->right);
            $result = $right->getLastChild();
            if ($result->id == '') {
                $result = $right;
            }
        }
        return $result;
    }


    /**
     * kerresés ha több van az elsőt adja vissza
     * @param string $value
     * @return BtreeNode ha nem talált {id: '', ...}
     */
    public function find(string $value): BtreeNode {
        if ($value == $this->value) {
            $result = $this;
        } else if ($value < $this->value) {
            if ($this->left == '') {
                $result = new BtreeNode('','');
            } else {
                $left = $this->readFromDB($this->left);
                $result = $left->find($value);
            }
        } else if ($value > $this->value) {
            if ($this->right == '') {
                $result = new BtreeNode('','');
            } else {
                $right = $this->readFromDB($this->right);
                $result = $right->find($value);
            }
        }
        $w = clone $result;
        while (($result->id != '') & ($result->value == $value)) {
            $w = clone $result;
            $result = $result->getPrevious();
        }
        $result = $w;
        return $result;
    }

    /**
     * Következő node elérése
     * @return BtreeNode  ha nincs return {id:''}
     */
    public function getNext(): BtreeNode {
        $result = new BtreeNode('','');
        if ($this->right != '') {
            $right = $this->readFromDB($this->right);
            $result = $right->getFirstChild();
            if ($result->id == '') {
                $result = $right;
            }
        } else if ($this->parent != '') {
            $parent = $this->readFromDB($this->parent);
            while (($parent->value < $this->value) & ($parent->parent != '')) {
                $parent = $this->readFromDB($parent->parent);
            }
            if ($parent->value >= $this->value) {
                $result = $parent;
            }
        }
        return $result;
    }

    /**
    * elöző node elérése, ha nincs return {id:''}
    * @return BtreeNode  ha nincs return {id:''}
    */
    public function getPrevious(): BtreeNode {
        $result = new BtreeNode('','');
        if ($this->left != '') {
            $left = $this->readFromDB($this->left);
            $result = $left->getLastChild();
            if ($result->id == '') {
                $result = $left;
            }
        } else if ($this->parent != '') {
            $parent = $this->readFromDB($this->parent);
            while (($parent->value >= $this->value) & ($parent->parent != '')) {
                $parent = $this->readFromDB($parent->parent);
            }
            if ($parent->value < $this->value) {
                $result = $parent;
            }
        }
        return $result;
    }

}

/*
 * TEST
  

 $root = new BtreeNode('root','root');
 $root->saveToDB();
 for ($i = 0; $i < 25; $i++) {
    $root->insertChild((string)rand(1000,9999),'data'.$i);
 }
 $root->insertChild('1953','data1953');
 $root->insertChild('1954','data1953');
 $root->insertChild('2314','data1953');
 for ($i = 26; $i < 51; $i++) {
    $root->insertChild((string)rand(1000,9999),'data'.$i);
 }
 $root->insertChild('1953','data1953');
 $root->insertChild('1954','data1953');
 $root->insertChild('2314','data1953');
 for ($i = 52; $i < 81; $i++) {
    $root->insertChild((string)rand(1000,9999),'data'.$i);
 }
 $root->insertChild('1953','data1953');
 $root->insertChild('1954','data1953');
 $root->insertChild('2314','data1953');
 
 echo JSON_encode($storage->items, JSON_PRETTY_PRINT)."\n\n\n";


 // find test
 $node = $root->find('1953');
 echo 'find 1953:'.JSON_encode($node)."\n";
 $node = $root->find('000');
 echo 'find 000:'.JSON_encode($node)."\n";

 // soros elérés test ASC
 echo 'soros elérés ASC'."\n";
 $cc = 0;
 $node = $root->getFirstChild();
 if ($node->id == '') {
    $node = $root;
 }
 while ($node->id != '') {
    echo JSON_encode($node)."\n";
    $node = $node->getNext();
    $cc++;
 }
 echo "================== cc=".$cc."\n";
 
 // soros elérés test DESC
 echo 'soros elérés DESC'."\n";
 $cc = 0;
 $node = $root->getLastChild();
 if ($node->id == '') {
    $node = $root;
 }
 while ($node->id != '') {
    echo JSON_encode($node)."\n";
    $node = $node->getPrevious();
    $cc++;
 }
 echo "================== cc=".$cc."\n";
 */

?>