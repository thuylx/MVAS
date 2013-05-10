<?
  ini_set("soap.wsdl_cache_enabled", "0");

  try
  {
    $s = '
<h1>php2wsdl</h1>
<br/>
Requirements:
php 5 (Reflection class used for WSDL generation);
php-soap (Soap API for the infrastructure implementation of its framework);
php-xml (Xslt as sample for testing RPC class objects);
SELinux (if activated) Permissive mode exception for security control leniency -> /usr/sbin/getenforce; /bin/cat /selinux/booleans/httpd_disable_trans; /usr/sbin/setsebool -P httpd_disable_trans on;));
MySQL-python (MySQL support for Python used in example to test runtime executable);
';
    printf("%s", $s);

    $s = '
<hr width="100%">
INPUT...
<hr width="100%">
';
    printf("%s", $s);

    $client = new SoapClient("http://127.0.0.1/soap/SoapService.php?wsdl&className=PhpXslt&", array("trace"=>1,"exceptions"=>1));
    echo "<pre>\n";
    $r = $client->getTest2($dbName="information_schema",$tableName="schemata","SCHEMA_NAME","%%",0,10);

    echo "<br/>SOAP request:\n";
    print(htmlspecialchars($client->__getLastRequest()));
    echo "<br/>SOAP response:\n";
    print(htmlspecialchars($client->__getLastResponse()));
    echo "</pre>\n";

    $s = '
<hr width="100%">
OUTPUT RESULT...
<hr width="100%">
';
    printf("%s", $s);
    print_r($r);

    $r = $client->getTest1("select schemata.* from information_schema.schemata where 1=1 and (lower(schema_name) like '%%') limit 0,10");
    $s = '
<hr width="100%">
ANOTHER TEST...
<hr width="100%">
<pre>
'.$r.'
</pre>
';
    printf("%s", $s);
  }
  catch (SoapFault $exception)
  {
    echo $exception;
  }
?>
