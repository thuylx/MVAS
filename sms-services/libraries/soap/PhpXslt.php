<?php
class PhpXslt
{
  function dbQuery
  (
    $query
  )
  {
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $rows = array();
    try
    {
      $conn = mysql_connect($dbhost,$dbuser,$dbpass);
      $result = mysql_query($query);
      while($fields = mysql_fetch_array($result, MYSQL_ASSOC))
      {
        $rows[] = $fields;
      }
      mysql_free_result($result);
      mysql_close($conn);
    }
    catch(Exception $e)
    {
    }
    return $rows;
  }

  function xmlxsl2xslt($xml, $xsl)
  {
    $xslt = '';

    $xslObj = new DOMDocument;
    $xslObj->loadXML($xsl);

    $xmlObj = new DOMDocument;
    $xmlObj->loadXML($xml);

    $procObj = new XSLTProcessor;
    $procObj->importStyleSheet($xslObj);

    $xslt = $procObj->transformToXML($xmlObj);

    return $xslt;
  }

  function getTest1
  (
    $input
  )
  {
    $command = sprintf("/usr/bin/python dbtest.py \"%s\"", $input);
    $output = `$command`;
    return $output;
  }

  function getTest2
  (
    $dbName,
    $tableName,
    $columnName,
    $likeCondition,
    $limitFrom,
    $limitOffset
  )
  {
    $colNames = array();

    $query = sprintf("select * from %s.%s where 1=1 and (%s.%s like '%s') limit %d,%d", $dbName, $tableName, $tableName, $columnName, mysql_real_escape_string($likeCondition), $limitFrom, $limitOffset);
error_log($query." DEBUG");
    $rows = self::dbQuery($query);
    if (sizeof($rows) <= 0)
    {
      return;
    }

    $xml = '';
    foreach($rows as $rowKey => $rowValue)
    {
      $xmlRow = '';
      $xslRow = '';
      foreach($rowValue as $colKey => $colValue)
      {
# Assign column names
        $colNames[$colKey] = $colKey;
# Accumulate column values
        $xmlRow .= '
<'.htmlentities($colKey).'>'.htmlentities($colValue).'</'.htmlentities($colKey).'>
';
# Accumulate template column mapping by its key
        $xslRow .= '
<td valign="top"><xsl:value-of select="'.$colKey.'"/><xsl:text> </xsl:text>&#160;</td>
';
      }
# Accumulate rows
      $xml .= '
<'.htmlentities($tableName).'>
'.$xmlRow.'
</'.htmlentities($tableName).'>
';
    }

# Finish Extensible Markup Language by encapsulation
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<'.htmlentities($dbName).'>
'.$xml.'
</'.htmlentities($dbName).'>
';

# Generate column names
    $xslLabel = '';
    foreach($colNames as $colKey => $colValue)
    {
      $xslLabel .= '
<td valign="top">'.$colValue.'</td>
';
    }

# Generate Extensible Stylesheet Language
    $xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:param name="dbName" select="'."'".$dbName."'".'"/>
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/'.$dbName.'">
    <html xmlns="http://www.w3.org/1999/xhtml">
      <head>
        <title><xsl:value-of select="$dbName"/></title>
      </head>
      <body>
        <h1>'.$tableName.'</h1>
        <ul>
          <table border="1" cellpadding="0" cellspacing="0">
            <tr>
'.$xslLabel.'
            </tr>
    <xsl:apply-templates select="'.$tableName.'">
      <xsl:sort select="'.$colNames['SCHEMA_NAME'].'" />
    </xsl:apply-templates>
          </table>
        </ul>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="'.$tableName.'">
<tr>
'.$xslRow.'
</tr>
  </xsl:template>

</xsl:stylesheet>
';

    $o = '
<style>
td {
  font-size:12px;
  font-family:Courier New;
}
</style>
<table cellpadding="0" cellspacing="0" border="1">
<tr>
<td valign="top">
<pre>
'.htmlentities($xml).'
</pre>
</td>
<td valign="top">
<pre>
'.htmlentities($xsl).'
</pre>
</td>
</tr>
</table>
';
    $s = sprintf("%s%s", self::xmlxsl2xslt($xml, $xsl), $o);
    return $s;
  }
}
?>
