<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * insert batch
 * */
function insert_batch($db,$table_name,$data)
{   
    $CI =& get_instance();
    $row = current($data);
    $keys = array_keys($row);
    foreach($keys as $key)
    {
        $fields[] = $db->protect_identifiers($key);
    }
    $fields = implode(',',$fields);
    $insert = "INSERT INTO ".$db->protect_identifiers($table_name,TRUE)."($fields)";
    $values = array();
        
    foreach($data as $row)
    {
        $value = array();
        foreach($row as $field)
        {
            $value[] = $db->escape($field);
        }
        $values[] = implode(',',$value);        
    }
    $values = implode("),(",$values);
    $values = "VALUES ($values);";
    $sql = $insert." ".$values;
    
    $db->query($sql);
    
    return $db->affected_rows();
}

/*End of file*/