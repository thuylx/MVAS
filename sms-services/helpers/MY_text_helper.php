<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
function highlight_info($string)
{
    return '<span style="color: #008000;">'.$string.'</span>';
}

function highlight_content($string)
{
    return '<span style="color: #0000FF;">'.htmlspecialchars($string).'</span>';
}

function highlight_scenario($string)
{
    return '<span style="color: #0000FF;">'.$string.'</span>';
}

function remove_accents($str)
{
    /*Vietnamese characters*/
    $convert_from=array(
        "�","�","?","?","�","�","?","?","?","?","?","a","?","?","?","?","?",
        "�","�","?","?","?","�","?","?","?","?","?",
        "�","�","?","?","i",
        "�","�","?","?","�","�","?","?","?","?","?","o",
        "?","?","?","?","?",
        "�","�","?","?","u","u","?","?","?","?","?",
        "?","�","?","?","?",
        "d",
        "�","�","?","?","�","�","?","?","?","?","?","A",
        "?","?","?","?","?",
        "�","�","?","?","?","�","?","?","?","?","?",
        "�","�","?","?","I",
        "�","�","?","?","�","�","?","?","?","?","?","O","?","?","?","?","?",
        "�","�","?","?","U","U","?","?","?","?","?",
        "?","�","?","?","?",
        "�"
        );
    
    /*No accent characters*/
    $convert_to=array(
        "a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a","a",
        "e","e","e","e","e","e","e","e","e","e","e",
        "i","i","i","i","i",
        "o","o","o","o","o","o","o","o","o","o","o","o",
        "o","o","o","o","o",
        "u","u","u","u","u","u","u","u","u","u","u",
        "y","y","y","y","y",
        "d",
        "A","A","A","A","A","A","A","A","A","A","A","A",
        "A","A","A","A","A",
        "E","E","E","E","E","E","E","E","E","E","E",
        "I","I","I","I","I",
        "O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O","O",
        "U","U","U","U","U","U","U","U","U","U","U",
        "Y","Y","Y","Y","Y",
        "D"
        );
    
    /*Convert function*/
    return str_replace($convert_from,$convert_to,$str);        
}
/*End of file*/