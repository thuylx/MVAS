<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *Convert ampersands to xml entities
 * The character of <,>,',",- have to be escape manually
 * @param string $str
 * @return string
 */
function xml_ampersand_escape($str)
{
    $str = preg_replace('/&[^; ]{0,6}.?/e', "((substr('\\0',-1) == ';') ? '\\0' : '&amp;'.substr('\\0',1))", $str);
    return $str;
}

function is_xml($string)
{
    $string = trim($string);
    $string = substr($string,0,1);
    return $string == '<';
}