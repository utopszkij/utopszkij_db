# Utopszkij_db

PHP-Ethereum noSQL adatbázis.

![logo](https://szakacskonyv.nfx.hu/utopszkij_db/images/logo.jpg)

## WEB SITE 
[https://szakacskonyv.nfx.hu/utopszkij_db](https://szakacskonyv.nfx.hu/utopszkij_db)

## Tulajdonságok

- Adat tárolás Ethereum blokkláncon okosszerződésekben,
- PHP interface
- Collections / Documents struktúra automatikus "id" képzés 
- Lekérdezés, modosítás, törlés "id" szerint
- indexelési lehetőség 
- Keresés összetett feltételek alapján (<, <=, =, <>, =>, >, and, or)
- Eredményben szereplő mezők szűkítése (select)
- Eredmény rendezés kiválasztott adatokra (orderBy) (UTF8 magyar ABC szerinti rendezés)
- Collectionok összekapcsolása (join, union)
- Csoportosítás (groupBy) adott mezőkre, COUNT, MAX, MIN, SUM, AVG függvények használhatóak 

## Használata (példa)
```
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
```

## Dokumentáció

[https://szakacskonyv.nfx.hu/utopszkij_db/doc/html/index.html](https://szakacskonyv.nfx.hu/utopszkij_db/doc/html/index.html)

doc/html/index.html

## Felhasznált harmadik féltől származó sw elemek 
- web3p  [https://github.com/web3p/web3.php](https://github.com/web3p/web3.php)  Köszönet a sw. fejlesztőinek.

A grafika a https://pixabay.com/ -ról származó alkotások felhasználásával készült.
						
## Lecensz

GNU v3

### A programot mindenki csak saját felelősségére használhatja.

## Információk informatikusok számára      

## Szükséges sw környezet
### futtatáshoz
- php 8+ 
- ethereum hálozat elérési adatok (URL, ACCOUNT, PASSWORD, gas, timeout) vagy lokális ether teszt hálózat

javasolt: [https://trufflesuite.com/ganache/](https://trufflesuite.com/ganache/)

### fejlesztéshez
- phpunit (unit test futtatáshoz)
- php szintaxist támogató forrás szerkesztő vagy IDE

## Telepítés
- a repo clonozása után:  composer install
- config.php elkészítése a a config-example.php alapján,


## A sw. dokumentáció előállítása

telepiteni kell a doxygen dokumentáció krátort.

[https://doxygen.nl/](doxygen)  Köszönet a sw. fejlesztőinek.

A telepitési könyvtáraknak megfelelően módosítani kell documentor.sh fájlt.

Ezután linux terminálban:

```
cd reporoot
./tools/documentor.sh
```
## verzió v1.0.0 Béta
Tesztelve lokális ganache-2.5.4 / linux teszt hálózaton

[https://trufflesuite.com/ganache/](https://trufflesuite.com/ganache/)

### *************************************
