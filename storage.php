<?php

/**
* storage réteg ethereum vagy vele solidity és RPC szinten kompatibilis
* blokkláncon történő tároláshoz
*/

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3\Personal;

/* config
define('ACCOUNT','0x937732E09A58144fB49A52b79eC3112520D98255');         // etherem account
define('GAS','0x202020');             // gas érték a tranzakciokhoz
define('URL','http://127.0.0.1:7545');// ehereum végpont url és port
define('TIMEOUT',5);                  // timeout az ethernet hivásokhoz
define('LOOPLIMIT',TIMEOUT * 10);     // az async hivások válaszára várakozó ciklus limit
define('ABI','[..]');
define('BYTECODE','0x.....');
*/

global $eth3, $result, $error;

class Storage {

    /**
     * Új rekord felvitele
     * @param $record
     * @return string új id
     */
    public static function write($record): string {
      global $error, $result, $web3;
      $error = 'working';

      // új okoszerződés létrehozása a blokklánon
      $eth = $web3->eth;
      $contract = new Contract($web3->provider, ABI);
      $contract->bytecode(BYTECODE)->new(JSON_encode($record, JSON_UNESCAPED_UNICODE), [
				'from' => ACCOUNT,
				'gas' => GAS],
      function ($err, $_result) use ($contract) {
         global $error, $result, $web3;
         if ($err == null) {
            // új okoszerződés address lekérdezése
            $contract->eth->getTransactionReceipt($_result,
	        function ($err, $_result) use ($contract) {
               global $error, $result;
               if ($err == null) {
                    $error = 'OK';
                    $result = $_result->contractAddress;
               } else {
                    $error = $err->getMessage();
                    $result = false;
               }
             });
          } else {
             $error = $err->getMessage();
             $result = false;
          }
      });

      // várakozás az aszinkron funkciók végrehajtására
      $i = 0;
      while (($i < LOOPLIMIT) & ($error == 'working')) {
	        sleep(0.1);
	        $i++;
      }
      if (($i < LOOPLIMIT) & ($error == 'OK')) {
            return $result;
      } else {
            echo 'Hiba lépett fel ('.$i.') '.$error.'<br />';
            return '';
      }
    } // write

    /**
     * meglévő rekord felülírása
     * @param string $id
     * @param $record
     */
    public static function update(string $id, $record) {
      global $error, $result, $web3;
	  $error = 'working';
      // okosszerződés method hívás
	  $eth = $web3->eth;
      $contract = new Contract($web3->provider, ABI);
      $contract->at($id)->send('update', JSON_encode($record, JSON_UNESCAPED_UNICODE), [
           'from' => ACCOUNT,
           'gas' => GAS],
      function ($err, $_result) {
           global $error, $result;
           if ($err == null) {
              $error = 'OK';
           } else {
              $error = $err->getMessage();
              $result = false;
           }
      });
      // várakozás az aszinkron hívás válaszára
      $i = 0;
      while (($i < LOOPLIMIT) & ($error == 'working')) {
	        sleep(0.1);
	        $i++;
      }
      if (($i >= LOOPLIMIT) | ($error != 'OK')) {
            echo 'Hiba lépett fel ('.$i.') '.$error.'<br />';
      }
    } // update

    /**
     * rekord olvasása id alapján
     * @param string $id
     * @return object $record
     */
    public static function read(string $id) {
      global $error, $result, $web3;
	  $error = 'working';
      // okosszerződés method hívás
	  $eth = $web3->eth;
      $contract = new Contract($web3->provider, ABI);
      $contract->at($id)->call('get', [
           'from' => ACCOUNT,
           'gas' => GAS],
      function ($err, $_result) {
           global $error, $result;
           if ($err == null) {
              $error = 'OK';
           	  $result = $_result;
           } else {
              $error = $err->getMessage();
              $result = false;
           }
      });
      // várakozás az aszinkron hívás válaszára
      $i = 0;
      while (($i < LOOPLIMIT) & ($error == 'working')) {
	        sleep(0.1);
	        $i++;
      }
      if (($i < LOOPLIMIT) & ($error == 'OK')) {
         if (is_array($result) & (count($result) > 0)) {
            $result = JSON_decode($result[0]);
         } else {
            $result = new \stdClass();
         }
      } else {
            echo 'Hiba lépett fel ('.$i.') '.$error.'<br />';
            $result = new \stdClass();
      }
      return $result;
    } // get

}

/**
* ez a rutin a local egyfile -os teszt tárolóval való kompatibiltás miatt kell
* a blokklánc -os tárolásnál mincs funkciója
*/
function StorageSave() {
}


?>