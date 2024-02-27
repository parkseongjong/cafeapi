<?php 

define('BASE_PATH', dirname(dirname(__FILE__)));
//define('APP_FOLDER','apis');
define('CURRENT_PAGE', basename($_SERVER['REQUEST_URI']));


require_once BASE_PATH.'/lib/MysqliDb.php';

// DATABASE
/*define('DB_HOST', "10.8.0.6");*/
define('DB_HOST', "arch.cybertronchain.com");
define('DB_USER', "web3_cybertron");
define('DB_PASSWORD', "db1234");
define('DB_NAME', "wallet");


define('DB_NAME2', "kiosk2");


/**
* Get instance of DB object
*/
function getDbInstance()
{
	return new MysqliDb(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME); 
}

function getDbInstance_k2()
{
	return new MysqliDb(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME2); 
}
/*
function getDbInstanceLatin1()
{
	return new MysqliDb(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME,null,'latin1');
}

function getDbInstanceUtf8MB4()
{
	return new MysqliDb(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME,null,'utf8mb4');
}
*/

