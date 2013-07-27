<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MY_Parser
{    
    var $l_delim = '{';
    var $r_delim = '}';    

    // --------------------------------------------------------------------

    /**
        *  Parse a String
        *
        * Parses pseudo-variables contained in the specified string,
        * replacing them with the data in the second param
        *
        * @access	public
        * @param	string
        * @param	array
        * @param	bool
        * @return	string
        */
    function parse($template, $data, &$count = NULL)
    {
        if ($template == '')
        {
                return FALSE;
        }
        
        if (is_object($data))
        {                
            $data = (method_exists($data,'get_all_properties'))?$data->get_all_properties():get_object_vars($data);                                
        }                

        //To try to parse pair first
        $count1 = 0;
        $temp = array(); //store single value
        foreach ($data as $key => $val)
        {
            if (is_array($val) || is_object($val)) 
            {
                $template = $this->_parse($key, $val, $template, $count1);
            }
            else
            {
                $temp[$key] = $val;
            }
        }
        
        //Parse single later
        $count2 = 0;
        foreach ($temp as $key => $val)
        {
            $template = $this->_parse($key, $val, $template, $count2);
        }        
        
        //Caculate total of replacement
        $count = $count1 + $count2;
        
        return $template;        
    }
    
    function _parse($key, $val, $string, &$count)
    {        
        if (is_object($val))
        {                
            $val = (method_exists($val,'get_all_properties'))?$val->get_all_properties():get_object_vars($val);
        }                   

        if ( ! is_array($val))
        {            
            return $this->_parse_single($key, $val, $string, $count);
        }
        else
        {         
            $temp = $this->_parse_pair($key,$val,$string,$count);
            if ($temp !== FALSE) 
            {
                return $temp;
            }
            else //no pair
            {
                foreach ($val as $subkey => $subval)
                {                    
                    $string = $this->_parse("$key.$subkey", $subval, $string, $temp);
                    $count += $temp;
                }     
                
                return $string;
            }

        }
    }
        
    /**
    *  Parse a single key/value
    *  Note that we concern on datatype for variable with prefix of $
    * @access	private
    * @param	string
    * @param	string
    * @param	string
    * @return	string
    */
    function _parse_single($key, $val, $string, &$count)
    {        
        //Parse string such as {mo.smsc_id}
        $string =  str_replace($this->l_delim.$key.$this->r_delim, $val, $string, $count1);

        //Parse varians (begin with $ such as {$mo.smsc_id})
        $val = (is_string($val))?"'$val'":$val;        
        $val = ($val === TRUE)?'TRUE':$val;
        $val = ($val === FALSE)?'FALSE':$val;
        $val = (is_null($val))?'NULL':$val;
        $string =  str_replace($this->l_delim.'$'.$key.$this->r_delim, $val, $string, $count2);

        //Record total number of replacement performed 
        $count = $count1 + $count2;                
        return $string;
    }

    // --------------------------------------------------------------------

    /**
    *  Parse a tag pair
    *
    * Parses tag pairs:  {some_tag} string... {/some_tag}
    *
    * @access	private
    * @param	string
    * @param	array
    * @param	string
    * @return	string
    */
    function _parse_pair($variable, $data, $string, &$count)
    {        
        if (FALSE === ($match = $this->_match_pair($string, $variable)))
        {
                return FALSE;
        }

        $str = array();
        foreach ($data as $index => $row)
        {
            $temp = $match['1'];

            if (is_object($row))
            {                
                $row = (method_exists($row,'get_all_properties'))?$row->get_all_properties():get_object_vars($row);
            }

            if (!is_array($row)) $row = array('key'=>$index,'value'=>$row);

            foreach ($row as $key => $val)
            {
                $temp = $this->_parse($key, $val, $temp, $count);
            }

            $str[] = $temp;
        }
        $match['3'] = (isset($match['3']))?$match['3']:'';
        $str = implode($match['3'], $str);

        return str_replace($match['0'], $str, $string);
    }

    // --------------------------------------------------------------------

    /**
        *  Matches a variable pair
        *
        * @access	private
        * @param	string
        * @param	string
        * @return	mixed
        */
    function _match_pair($string, $variable)
    {
            if ( ! preg_match("|" . preg_quote($this->l_delim . $variable . $this->r_delim) . "(.+?)(`(.*)`)?". preg_quote($this->l_delim . '/' . $variable . $this->r_delim) . "|s", $string, $match))
            {
                    return FALSE;
            }
            return $match;
    }
        
    
    /**
     *List all of vars in input $string
     * @param string $string
     * @return array 
     */
    public function get_vars_list($string)
    {
        //$variable = "[0-9a-zA-Z_][0-9a-zA-Z_\-\.\,\+ \(\)\=':]*";
        $variable = "[0-9a-zA-Z][^\\".$this->l_delim."\\".$this->r_delim."]*";
        
        //Clear pairs first
//        $string = preg_replace("|(" . preg_quote($this->l_delim) . '(' . $variable . ')' . preg_quote($this->r_delim) . ")(.+?)(`(.*)`)?". preg_quote($this->l_delim) . '\/\2' . preg_quote($this->r_delim) . "|s", '$1', $string);
        $string = preg_replace("|(" . preg_quote($this->l_delim) . '(.*)' . preg_quote($this->r_delim) . ")(.+?)(`(.*)`)?". preg_quote($this->l_delim) . '\/\2' . preg_quote($this->r_delim) . "|s", '$1', $string);
        //Look up for vars        
        $pattern = "|" . preg_quote($this->l_delim) . "(" . preg_quote('$') . "?" . $variable . ")" . preg_quote($this->r_delim) ."|";        
        preg_match_all($pattern,$string,$funcs);        
        $funcs = $funcs[1];       

        return $funcs;
    }        
}
/*End of file*/