<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Get existing elements
 * Works mostly like function "elements" in CI array helper
 * Returns only the array items specified. Will not return if it is not set.
 *
 * @access	public
 * @param	array
 * @param	array
 * @param	mixed
 * @return	mixed	depends on what the array contains
 */
function exist_elements($items, $array)
{
	$return = array();
	
	if ( ! is_array($items))
	{
		$items = array($items);
	}
    	        
	foreach ($items as $item)
	{
		if (array_key_exists($item,$array))
		{
			$return[$item] = $array[$item];
		}
	}

	return $return;
}

/**
 * Returns specific key/column from an array of objects.
 * */
function array_pluck($key, $array)
{
    if (is_array($key) || !is_array($array)) return array();
    $funct = create_function('$e', 'return is_array($e) && array_key_exists("'.$key.'",$e) ? $e["'. $key .'"] : null;');
    return array_map($funct, $array);
}


/*End of file*/