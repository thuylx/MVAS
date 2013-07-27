<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MY_Parser extends CI_Parser
{    
    //Parse $string by parameter which prefixed by $key_chain in $array
    //Called recursively in function of "parse_params"
    private function _parse($string,$array,$key_chain='',$escape_characters = NULL, &$count = NULL)
    {
        foreach ($array as $key=>$value)
        {
            if (is_object($value))
            {                
                $value = (method_exists($value,'get_all_properties'))?$value->get_all_properties():get_object_vars($value);                                
            }
            
            if (is_array($value))
            {
                $new_key_chain = ($key_chain=='')?$key:"$key_chain.$key";
                $string = $this->_parse($string,$value,$new_key_chain,$escape_characters,$count);
            }
            else
            {
                $key = ($key_chain == '')?$key:$key_chain.'.'.$key;
                
                if ($escape_characters)
                {               
                    foreach ($escape_characters as $character)
                    {
                        $repl_characters[] = '\\'.$character;
                    }
                    $value = str_replace($escape_characters,$repl_characters,$value);
                }
                                
                $string = str_replace('{'.$key.'}',$value,$string,$temp);
                if (!is_null($count)) $count += $temp;
            }            
        }
        
        return $string;
    } 
    
    /**
     * Replace application environmental parameters by its value or remove it if no matched value found
     * @param   $string: input string;
     * @param   $data: input object or array data to parse;
     * @param   $escape_characters: a character or an array of character which will be escapted.
     *          by adding letter backslash before.
     * @param   interger &$count: If passed, this will be set to the number of replacements performed. 
     * @return  parsed string.
     * */
    public function parse($string,$data,$escape_characters = NULL, &$count = NULL)
    {           
        if ($string == '')
        {
            return;
        }
        
        if (is_object($data))
        {                
            $data = (method_exists($data,'get_all_properties'))?$data->get_all_properties():get_object_vars($data);                                
        }

        if (is_string($escape_characters))
        {
            $escape_characters = array($escape_characters);
        }                
                        
        //Parse parameters
        $string = $this->_parse($string,$data,'',$escape_characters,$count);
        
        return $string;
    }    
    
    /**
     *List all of vars in input $string
     * @param string $string
     * @return array 
     */
    public function get_vars_list($string)
    {
        //Look up for vars
        $pattern = '/{(@?[0-9a-zA-Z_][0-9a-zA-Z_\-\.\+ ]*)}/i';
        preg_match_all($pattern,$string,$funcs);
        $funcs = $funcs[1];       
        return $funcs;
    }
}
/*End of file*/