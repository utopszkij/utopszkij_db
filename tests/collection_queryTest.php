<?php
/**
 * Ez a teszt lokális Ganache teszt blokkláncon
 * kb 30 másodpercig fut és 0.45 ETH -t használ fel.
 */
global $error, $result, $web3;
include_once (__DIR__.'/../eth_init.php');
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase {
	
	protected $collection;
	protected $query;

	function __construct() {
		parent::__construct();
	}
    
	// ez csak egyszer fut
	public function test_start()  {
		$this->collection->create(['id','name']);
		$this->collection->insert(Doc(["name" => "Teszt Elek", "phone" => "+36302106501"]));
		$this->collection->insert(Doc(["name" => "Gipsz Jakab", "phone" => "+36301234567"]));
		$this->collection->insert(Doc(["name" => "Noname", "phone" => "none"]));
		$this->collection->insert(Doc(["name" => "zzzz"]));
		$this->collection->insert(Doc(["name" => "élet"]));
		$this->collection->insert(Doc(["name" => "gggg"]));
		$this->collection->insert(Doc(["name" => "aaaa"]));
		$this->collection->insert(Doc(["name" => "ELEMÉR"]));
		$id = $this->collection->insert(Doc(["name" => "Éva"]));    
		$rec = $this->collection->getById($id);
		$this->assertEquals($rec->name,'Éva');
	}


	// ez minden egyes test rutin előtt lefut
	public function setup():void {
		$this->collection = new Collection('test1');
		$this->query = new Query('test1');
	}

    public function test_getByFilter_id_0_none() {
		$recs = $this->collection->getByFilter('id','','none');
		$this->assertEquals(count($recs),9);
	}

    public function test_getByFilter_name_élet_élet() {
		$recs = $this->collection->getByFilter('name','élet','élet');
		$this->assertEquals(count($recs),1);
		$this->assertEquals($recs[0]->name,'élet');
	}

    public function test_getByFilter_name_0_none() {
		$recs = $this->collection->getByFilter('name','0','none');
		$this->assertEquals('ELEMÉR',$recs[0]->name);
		$this->assertEquals('élet',$recs[8]->name);
	}

    public function test_all_nowhere_noselect() {
		$recs = $this->collection->all();
		$this->assertEquals(count($recs),9);
	}

	public function test_all_where_select() {
		$recs = $this->collection->select(['id','name'])
		->where('id','<>','')
		->where('name','>','0')
		->orWhere('name','=','Noname')
		->all();
		$this->assertEquals(count($recs),9);
	}
		
	public function test_update() {
		$recs = $this->collection->where('name','=','gggg')->all();
		$id = $recs[0]->id;
		$res = $this->collection->updateById($id, Doc(['name' => 'javitva']));
		$this->assertEquals(true,$res);
		$recs = $this->collection->where('name','=','gggg')->all();
		$this->assertEquals(0,count($recs));
		$recs = $this->collection->where('name','=','javitva')->all();
		// $this->assertEquals(1,count($recs));
	}	

	public function test_delete() {
		$recs = $this->collection->where('name','=','ELEMÉR')->all();
		$id = $recs[0]->id;
		$this->collection->deleteById($id);
		$recs = $this->collection->where('name','=','ELEMÉR')->all();
		$this->assertEquals(count($recs),0);
	}	

	public function test_createIndex() {
		$recs = $this->collection->createIndex('phone');
		$recs = $this->collection->getByFilter('phone','0','none');
		$this->assertEquals(3, count($recs));
	}	

	public function test_dropIndex() {
		$recs = $this->collection->dropIndex('phone');
		$this->assertEquals(0, 0);
	}	

	public function test_dropIndex_id() {
		$recs = $this->collection->dropIndex('id');
		$this->assertEquals(0, 0);
	}	

	public function test_deleteById() {
		$recs = $this->collection->all();
		$id = $recs[0]->id;
		$res = $this->collection->deleteById($id);
		$this->assertEquals(true, $res);

		// getByidId deleted (de létező) record
		$rec = $this->collection->getById($id);
		$this->assertEquals('{}', JSON_encode($rec));

		// getById nem létező id
		$wid = (string)$id;
		if (strlen($wid) >6) {
			$wid[5] = '0'; // eth -n fut
			$wid[6] = '0';
		} else {
			$wid = '199999'; // onefile tárolón fut
		}

		$rec = $this->collection->getById($wid);
		$this->assertEquals('{}', JSON_encode($rec));

		// updateById deleted (de létező) record
		$res = $this->collection->updateById($id, Doc(["name" => ""]));
		$this->assertEquals(false, $res);

		// updateById nem létező id
		$res = $this->collection->updateById($wid, Doc(["name" => ""]));
		$this->assertEquals(false, $res);

	}	

	public function test_query_1() {
		$recs = $this->query->select(['id','name'])
		->where('name','<>','')
		->where('id','>','')
		->orWhere('phone','=','66')
		->orderBy(['name','id'])
		->offset(0)
		->limit(3)
		->all();
		$this->assertEquals(3, count($recs));
	}

	public function test_query_union() {
		$q1 = new Query('test1');
		$q1->where('name','=','Gipsz Jakab');

		$q2 = new Query('test1');
		$recs = $q2->where('name','=','élet');

		$recs = $this->query->select(['id','name'])
		->union($q1)
		->union($q2)
		->all();
		$this->assertEquals(2, count($recs));
	}

	public function test_join() {
		$col2 = new Collection('addresses');
		$col2->create(['id']);
		$col2->insert(Doc(["name" => "Gipsz Jakab", "type" => "ideiglenes",
			"irsz" => "1027", "addr" => "Szász Károly u 10"]));
		$col2->insert(Doc(["name" => "Gipsz Jakab", "type" => "allando",
			"irsz" => "1036", "addr" => "Nagy u 26"]));
		$col2->insert(Doc(["name" => "élet", "type" => "allando",
			"irsz" => "1119", "addr" => "Kiss u 3"]));
		
		$recs = $this->query->join('LEFT OUTER', 'addresses','name','name','a')
		->all();	
		$this->assertEquals(8, count($recs));
		
		$recs = $this->query->join('INNER', 'addresses','name','name','a')
		->all();	
		$this->assertEquals(3, count($recs));

	}

	public function test_distinct() {
		$q = new Query('addresses');
		$recs = $q->all();
		$this->assertEquals(3, count($recs));

		$recs = $q->select(["name"])->distinct()->all();
		$this->assertEquals(2, count($recs));

	}

	public function test_groupBy_COUNT_SUM() {
		$q = new Query('addresses');
		$recs = $q->all();
		$this->assertEquals(3, count($recs));

		$recs = $q->select(["name", ['COUNT',"irsz","cc"],['SUM','irsz','sumirsz']])
		->groupBy(["name"])
		->orderBy(["name"])
		->all();
		$this->assertEquals('élet', $recs[0]->name);
		$this->assertEquals('Gipsz Jakab', $recs[1]->name);
		$this->assertEquals(2, $recs[1]->cc);
		$this->assertEquals(1, $recs[0]->cc);
		$this->assertEquals('2063', $recs[1]->sumirsz);
		$this->assertEquals('1119', $recs[0]->sumirsz);
	}
	public function test_groupBy_MIN_MAX_AVG() {
		$q = new Query('addresses');
		$recs = $q->all();
		$this->assertEquals(3, count($recs));

		$recs = $q->select(["name", 
			['MAX',"irsz","maxirsz"],
			['MIN','irsz','minirsz'],
			['AVG','irsz','avgirsz']])
		->groupBy(["name"])
		->orderBy(["name"])
		->all();
		$this->assertEquals("1119", $recs[0]->maxirsz);
		$this->assertEquals("1036", $recs[1]->maxirsz);
		$this->assertEquals("1119", $recs[0]->minirsz);
		$this->assertEquals("1027", $recs[1]->minirsz);
		$this->assertEquals("1119", $recs[0]->avgirsz);
		$this->assertEquals("1031.5", $recs[1]->avgirsz);

	}


	public function test_end() {
		storageSave();
		$this->assertEquals(0,0);
	}	

}

?>
