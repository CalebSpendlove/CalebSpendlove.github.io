<?php
/*

Oxygen Webhelp plugin
Copyright (c) 1998-2015 Syncro Soft SRL, Romania.  All rights reserved.
Licensed under the terms stated in the license file EULA_Webhelp.txt 
available in the base directory of this Oxygen Webhelp plugin.

*/

class Product {
	private $dbConnectionInfo;
	private $version;
	function __construct($dbConnectionInfo,$version){
		$this->dbConnectionInfo=$dbConnectionInfo;
		$this->version=$version;
	}
	/**
	 * @return array all products that share comments with this one
	 */
	function getSharedWith(){
		$toReturn= array();
		$db= new RecordSet($this->dbConnectionInfo,false,true);
        $query = "Select product,value from webhelp where parameter='name' and version='". $db->sanitize($this->version) ."' ";

        if(defined('__SHARE_WITH__')) {
            $query .= "AND product in (";
            $shareArray = explode(",", __SHARE_WITH__);
            foreach ($shareArray as $key => $value) {
                $query .= "'" . $db->sanitize($value) . "', ";
            }
            $query = substr($query,0,-2) . ");";
        }

        error_log($query);

        $prds=$db->Open($query);

		if ($prds>0){
			while ($db->MoveNext()){
				$product=$db->Field('product');
				$value=$db->Field('value');
				$toReturn[$product]=$value;
			
			}
		}
		$db->close();
		return $toReturn;
	}
}
?>