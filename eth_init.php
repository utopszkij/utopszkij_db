<?php
/**
 * Ez egy include amit a programok elejére kell beilleszteni
 */

require(__DIR__.'/vendor/autoload.php');
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3\Personal;

global $error, $result, $web3;

/* 
//+ tesztelés storage_onefile használatával
	if (file_exists('onefiledb.dat')) {
		unlink('onefiledb.dat');
	}   
	include_once './storage_onefile.php'; 
	include_once './btree.php'; 
	// root elem létrehozása
	$root = new BtreeNode('root',JSON_decode('{"root" : "root" }'));
	$root->saveToDB();
	define('ROOT_ID',0);
//-	
*/ 

include_once __DIR__.'/config.php';

//+ Futtatás ethereum blokláncon 

	$web3 = new Web3(new HttpProvider(new HttpRequestManager(URL, TIMEOUT)));

	include_once __DIR__.'/storage.php'; 
	include_once __DIR__.'/btree.php'; 
	if (URL == 'http://127.0.0.1:7545') {
		// lokális teszt hálozaton fut
		// test account lekérdezése (unLockolva van)
		$eth = $web3->eth;
		$eth->accounts(function ($_err, $accounts) {
		   define('ACCOUNT',$accounts[0]);
		   // test root elem létrehozása:
		   $root = new BtreeNode('root',JSON_decode('{"root" : "root" }'));
		   $root->saveToDB();
		   define('ROOT_ID',$root->id);
		});
		sleep(5);
	 } else {
		// éles publikus ethernet hálozaton fut
		// account unlock
		$personal = $web3->personal;
		$personal->unlockAccount(ACCOUNT, PASSWORD, function ($err, $unlocked) {
			  if ($err !== null) {
				 echo "\n Error: ".$err->getMessage()."\n"; exit();
			  }
			  if ($unlocked) {
				 if (!defined('ROOT_ID')) {
					// root item létrehozása
					$root = new BtreeNode('root',JSON_decode('{"root" : "root" }'));
					$root->saveToDB();
					if (($error == '') | ($error == 'OK'))
					   echo "\n Rot item created. ROOT_ID='$root->id' \n"; exit(); 
					} else {
					   echo "\n Error in create root item $error \n"; exit();
					}
			  } else {
				  echo "\n Account isn\'t unlocked \n"; exit();
			  }
		});   
}
//-


include_once __DIR__.'/collection.php';
include_once __DIR__.'/query.php';

?>
