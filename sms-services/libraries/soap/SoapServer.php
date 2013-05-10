<?php
$className = $_REQUEST['className'];

require_once(sprintf("%s.php", $className));

ini_set("soap.wsdl_cache_enabled", "0");

$server = new SoapServer(sprintf("http://127.0.0.1/soap/SoapService.php?wsdl&className=%s&", rawurlencode($className)));

$server->setClass($className);
$server->handle();
?>
