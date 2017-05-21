<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace XDB;

/**
 * @author noxymon
 */
class XDBException extends \Exception {}

/**
 * @author noxymon
 */
class XDB {

    private $DbPath;

    function __construct($path) {
        if (substr($path, strlen($path) - 1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        $this->DbPath = $path;

        if (!file_exists($path)) {
            try {
                mkdir($path, 0777, true);
            } catch (Exception $exc) {
                throw new XDBException("Record not found !");
            }
        }
    }
    
    function initTable($tableName){
        $incrementFile = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . '.meta' . DIRECTORY_SEPARATOR . 'increment';

        if (!file_exists($this->DbPath . $tableName)) {
            try {
                mkdir($this->DbPath . $tableName, 0777, true);
                mkdir($this->DbPath . $tableName . DIRECTORY_SEPARATOR . '.meta', 0777, true);
                
                /*
                 * write index.php in all of root table folder
                 */
                $fileIndexHandle = fopen($this->DbPath.'index.php', 'w');
                fwrite($fileIndexHandle, "There's nothing, just empty.");
                fclose($fileIndexHandle);
                
                /*
                 * write increment id file, to record latest increment id
                 * written in table
                 */
                $fileHandle = fopen($incrementFile, 'w');
                fwrite($fileHandle, '0');
                fclose($fileHandle);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }
    }
    
    function countTable($tableName){
        if (file_exists($this->DbPath . $tableName)) {
            $files = scandir($this->DbPath . $tableName);
            return (count($files)-3);
        }else{
            throw new XDBException("There no table such as $tableName !");
        }
    }

    function insert($tableName, array $column, array $value) {

        $incrementFile = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . '.meta' . DIRECTORY_SEPARATOR . 'increment';

        if (!file_exists($this->DbPath . $tableName)) {
            $this->initTable($tableName);
        }

        $incrementRead = fopen($incrementFile, 'r');
        $incrementNum = (int) fread($incrementRead, filesize($incrementFile));
        fclose($incrementRead);

        $newFileData = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . ($incrementNum + 1);
        $newData = array();

        for ($index = 0; $index < count($column); $index++) {
            $newData[$column[$index]] = $value[$index];
        }
        
        /*
         * write record content
         */
        $writeDataHandle = fopen($newFileData, 'w');
        fwrite($writeDataHandle, json_encode($newData));
        fclose($writeDataHandle);

        /*
         * update the increment record
         */
        unlink($incrementFile);
        $incrementWrite = fopen($incrementFile, 'w');
        fwrite($incrementWrite, ($incrementNum + 1));
        fclose($incrementWrite);
    }

    function loadAll($tableName) {
        if (file_exists($this->DbPath . $tableName)) {
            $files = scandir($this->DbPath . $tableName);
            
            /*
             * constant number 3 obtained from number of restrcited directory : 
             * '.', '..', '.meta'
             */
            for ($index = 3; $index < count($files); $index++) {

                /*
                 * read each file to get column
                 */
                if ($files[$index] !== '.' && $files[$index] !== '..' && $files[$index] !== '.meta') {
                    $fileName = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . $files[$index];
                    $fileHandle = fopen($fileName, 'r');
                    $_content = fread($fileHandle, filesize($fileName));
                    $contentFile = json_decode($_content, true);

                    $contentKey = array_keys($contentFile);

                    for ($ci = 0; $ci < count($contentKey); $ci++) {
                        $data[$files[$index]][$contentKey[$ci]] = $contentFile[$contentKey[$ci]];
                    }
                }
            }
            return json_encode($data);
        }else{
            throw new XDBException("There no table such as $tableName !");
        }
    }
    
    function loadById($tableName, $id) {
        $fileName = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . $id;
        if (file_exists($fileName)) {
            $fileHandle = fopen($fileName, 'r');
            $_content = fread($fileHandle, filesize($fileName));
            $contentFile = json_decode($_content, true);

            $contentKey = array_keys($contentFile);

            for ($ci = 0; $ci < count($contentKey); $ci++) {
                $data[$id][$contentKey[$ci]] = $contentFile[$contentKey[$ci]];
            }
        }else{
            throw new XDBException("Record not found !");
        }
        return json_encode($data);
    }
    
    function deleteById($tableName, $id){
        $fileName = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . $id;
        if (file_exists($fileName)) {
            try {
                unlink($fileName);
            } catch (Exception $exc) {
                throw new XDBException($exc);
            }
        }else{
            throw new XDBException("Record not found !");
        }
    }
    
    function updateById($tableName, $id, array $column, array $value){
        $fileName = $this->DbPath . $tableName . DIRECTORY_SEPARATOR . $id;
        if (file_exists($fileName)) {
            if (count($column) === count($value)) {
                $dataCopy = null;
                $fileHandle = fopen($fileName, 'r');
                $_content = fread($fileHandle, filesize($fileName));
                $contentFile = json_decode($_content, true);

                $contentKey = array_keys($contentFile);
                
                /*
                 * copy all data to memory
                 */
                for ($ci = 0; $ci < count($contentKey); $ci++) {
                    $dataCopy[$contentKey[$ci]] = $contentFile[$contentKey[$ci]];
                }
                
                /*
                 * delete current file
                 */
                unlink($fileName);
                
                /*
                 * modify new value
                 */
                for ($i = 0; $i < count($column); $i++) {
                    for ($j = 0; $j < count($contentKey); $j++) {
                        if ($contentKey[$j] === $column[$i]) {
                            $dataCopy[$column[$i]] = $value[$i]; 
                        }
                    }
                }

                /*
                 * Write new file
                 */
                $writeDataHandle = fopen($fileName, 'w');
                fwrite($writeDataHandle, json_encode($dataCopy));
                fclose($writeDataHandle);
            }else{
                throw new XDBException("Parameter column and value not equals !");
            }
        }else{
            throw new XDBException("Record not found !");
        }
    }
}