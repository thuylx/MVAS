<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'dbsrv.mvas.vn';
$db['default']['username'] = 'mvas';
$db['default']['password'] = 'Sec@6789ret';
$db['default']['database'] = 'dev_app';
$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = 'm_';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = FCPATH."cache/dbcache/";
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;
// Add these parameters for get inserted MT ids after insert_batch
$db['default']['auto_incremental_offset'] = 1;
//$db['default']['mysql_max_allowed_packet'] = 31457280;

$db['mgw']['hostname'] = 'dbsrv.mvas.vn';
$db['mgw']['username'] = 'kannel';
$db['mgw']['password'] = 'Sec@6789ret';
$db['mgw']['database'] = 'dev_gw';
$db['mgw']['dbdriver'] = 'mysqli';
$db['mgw']['dbprefix'] = '';
$db['mgw']['pconnect'] = TRUE;
$db['mgw']['db_debug'] = TRUE;
$db['mgw']['cache_on'] = FALSE;
$db['mgw']['cachedir'] = FCPATH."cache/dbcache/";
$db['mgw']['char_set'] = 'utf8';
$db['mgw']['dbcollat'] = 'utf8_general_ci';
$db['mgw']['swap_pre'] = '';
$db['mgw']['autoinit'] = TRUE;
$db['mgw']['stricton'] = FALSE;
// Add these parameters for get inserted MT ids after insert_batch
$db['mgw']['auto_incremental_offset'] = 1;
//$db['mgw']['mysql_max_allowed_packet'] = 31457280;

/* End of file database.php */
/* Location: ./application/config/database.php */