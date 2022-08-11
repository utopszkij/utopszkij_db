<?php
?>
<html>
  <head>
     <meta charset="UTF-8">
  </head>
  <body style="padding:20px;">
      <h1>Utopszkij_db</h1>
      <p><img src="images/logo.jpg" /></p>
      <h2>PHP-Ethereum noSQL adatbázis.</h2>

      <h3>Tulajdonságok</h3>
      <ul>
      <li>Adat tárolás Ethereum blokkláncon okosszerződésekben,</li>
      <li>PHP interface</li>
      <li>Collections / Documents struktúra automatikus "id" képzés </li>
      <li>Lekérdezés, modosítás, törlés "id" szerint</li>
      <li>indexelési lehetőség </li>
      <li>Keresés összetett feltételek alapján (<, <=, =, <>, =>, >, and, or)</li>
      <li>Eredményben szereplő mezők szűkítése (select)</li>
      <li>Eredmény rendezés kiválasztott adatokra (orderBy) (UTF8 magyar ABC szerinti rendezés)</li>
      <li>Collectionok összekapcsolása (join, union)</li>
      <li>Csoportosítás (groupBy) adott mezőkre, COUNT, MAX, MIN, SUM, AVG függvények használhatóak </li>
      </ul>
      <h3>Használata (példa)</h3>
      <pre><code>
      &lt;?php
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
      ?&gt;
      </code></pre>

      <h3>Dokumentáció</h3>

      <a href="doc/html/index.html">link</a>

      <h3>Forrás program</h3>

      <a href="https://github.com/utopszkij/utopszkij_db">github</a>

      <h3>Felhasznált harmadik féltől származó sw elemek </h3>
      <p>web3p  https://github.com/web3p/web3.php  Köszönet a sw. fejlesztőinek.</p>
      <p>A grafika a https://pixabay.com/ -ról származó alkotások felhasználásával készült.</p>

      <h3>Lecensz</h3>
      <p>GNU v3</p>

      <p><strong>A programot mindenki csak saját felelősségére használhatja.</strong></p>

      <h3>Tesztelve:  lokális ganache-2.5.4 / linux teszt hálózaton.</h3>
      <h3>Információk informatikusok számára</h3>      

      <h4>Szükséges sw környezet</h4>
      <h5>futtatáshoz</h5>
      <ul>
      <li>php 8+ </li>
      <li>ethereum hálozat elérési adatok (URL, ACCOUNT, PASSWORD, gas, timeout) vagy lokális ether teszt hálózat</li>
      </ul>
      <p>javasolt: https://trufflesuite.com/ganache/ teszt hálózat</p>

      <h5>fejlesztéshez</h5>
      <ul>
      <li>phpunit (unit test futtatáshoz)</li>
      <li>php szintaxist támogató forrás szerkesztő vagy IDE</li>
      </ul>
      <h4>Telepítés</h4>
      <ul>
      <li>a repo clonozása után:  composer install</li>
      <li>config.php elkészítése a a config-example.php alapján,</li>
      </ul>
  </body>
</html> 
