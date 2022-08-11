<?php

global $items,$error;
$items = [];
$error = '';

if (file_exists('onefiledb.dat')) {
    $items = file('onefiledb.dat');
}

class Storage {

    /**
     * Új rekord felvitele
     * @param object $record
     * @return string új id
     */
    public static function write($record): string {
        global $items;
        $record['id'] = count($items);
        $items[] = JSON_encode($record, JSON_UNESCAPED_UNICODE); 
        return (string) $record['id'];
    }
    /**
     * meglévő rekord felülírása
     * @param object $record    public string $error = '';

     */
    public static function update($id, $record) {
        global $items;
        if (isset($items[$id])) {
            $items[$record['id']] = JSON_encode($record, JSON_UNESCAPED_UNICODE); 
        } else {
            $this->error = 'NOT FOUND';
        }   
    }
    /**
     * rekord olvasása id alapján
     * @param string $id
     * @return object $record
     */
    public static function read(string $id) {
        global $items;
        if (isset($items[$id])) {
            return JSON_decode($items[$id]);
        } else {
            return new \stdClass();
        }    
    }

}

function StorageSave() {
    global $items;
    $fp = fopen('onefiledb.dat','w+');
    foreach ($items as $item) {
        fwrite($fp,$item."\n");
    }
    fclose($fp);
}


?>