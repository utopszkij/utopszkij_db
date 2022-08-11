<?php
/**
 */
global $error, $result, $web3;
include_once (__DIR__.'/../eth_init.php');

    $collection = new Collection('test_collection');
	$collection->create(['id','name']);
	$collection->insert(Doc(["name" => "Teszt Elek", "phone" => "+36302106501"]));
	$collection->insert(Doc(["name" => "Gipsz Jakab", "phone" => "+36301234567"]));
	$collection->insert(Doc(["name" => "Noname", "phone" => "none"]));
	$collection->insert(Doc(["name" => "Joska"]));
	$collection->insert(Doc(["name" => "Kis Pista"]));
	$collection->insert(Doc(["name" => "Peter"]));
	$collection->insert(Doc(["name" => "Miska"]));
	$collection->insert(Doc(["name" => "Tibi"]));
    $id = $collection->insert(Doc(["name" => "Ilona"]));    

    echo 'Read by id '."\n";
	$rec = $collection->getById($id);
	echo JSON_encode($rec)."\n";

    echo 'Updateby id '."\n";
	$rec = $collection->updateById($id, Doc(["name" => "Ilonka"]));
	$rec = $collection->getById($id);
	echo JSON_encode($rec)."\n";

    echo 'Query'."\n";
    $query = new Query('test_collection');
    $recs = $query->select(['id','name'])
		->where('name','<>','')
		->where('id','>','')
		->orWhere('phone','=','66')
		->orderBy(['name','id'])
		->offset(0)
		->limit(3)
		->all();
    echo JSON_encode($recs);
?>
