<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Validate an expression.
 * */
function exp_validate($expression)
{   
    // Validate expression
    $var = '\$\{[a-zA-Z_][a-zA-Z0-9_.]*\}';
    $string = '\'.*\'';
    $number = '-?[0-9]*';
    $single_expression = "($var|$string|$number) *(=|<>|!=|>|<|>=|<=) *($var|$string|$number)";
    
    $valid = preg_match('/^'.$single_expression.'( *(and|or) *'.$single_expression.')*$/i',$expression);
    return $valid;
}

/**
 * Convert an expression to PHP executable format
 * */
function exp_standarize($expression)
{
    if ( ! exp_validate($expression))
    {
        return FALSE;
    }
    
    $var = '\$\{[a-zA-Z_][a-zA-Z0-9_.]*\}';
    $string = '\'.*\'';
    $number = '-?[0-9]*';
    $single_expression = "($var|$string|$number) *(=|<>|!=|>|<|>=|<=) *($var|$string|$number)";
    
    do
    {        
        $formulars = array("/($single_expression) *or *($single_expression)/i","/($single_expression) *and *($single_expression)/i",'/(\$\{[^$]+\}) *= *(.+)/','/(\$\{[^$]+\}) *<> *(.+)/');
        $corrected = array("$1 || $5","$1 && $5","'$1'==$2","'$1'!=$2");
        $expression = preg_replace($formulars,$corrected,$expression,-1,$count);                    
    }while ($count);                                                   

    return $expression;    
}
/*End of file*/