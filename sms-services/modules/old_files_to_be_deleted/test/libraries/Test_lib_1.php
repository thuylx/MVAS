<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test_lib
{
	private $vars;
    
    public function __construct()
    {
        write_log('debug','Constructing');
        $vars = array();              
    }
    
    public function __get($key) 
    {        
        if ( ! isset($this->vars[ $key ]))
        {
            return NULL;
        }
        return $this->vars[ $key ];
    }
    
    public function __set($key,$value) 
    {
        $this->vars[ $key ] = $value;
    }    
    
    // check if value is set
    public function is_set($key)
    {
        return isset($this->vars[ $key ]);
    }    

	// gets a value
	public function get($var)
	{
		return $this->vars[$var];
	}

	// sets a key => value
	public function set($key,$value)
	{
		$this->vars[$key] = $value;
	}

	// loads a key => value array into the class
	public function load($array)
	{
		if(is_array($array))
		{
			foreach($array as $key=>$value)
			{
				$this->vars[$key] = $value;
			}
		}
	}

	// empties a specified setting or all of them
	public function unload($vars = '')
	{
		if($vars)
		{
			if(is_array($vars))
			{
				foreach($vars as $var)
				{
					unset($this->vars[$var]);
				}
			}
			else
			{
				unset($this->vars[$vars]);
			}
		}
		else
		{
			$this->vars = array();
		}
	}

	/* return the object as an array */
	public function get_all()
	{
		return $this->vars;
	}
    
    //Parse $string by parameter which prefixed by $key_chain in $array
    //Called recursively in function of "parse_params"
    private function _parse($string,$array,$key_chain='')
    {
        foreach ($array as $key=>$value)
        {
            if (is_array($value))
            {
                $key_chain = ($key_chain=='')?$key:"$key_chain.$key";
                $string = $this->_parse($string,$value,$key_chain);
            }
            else
            {
                $string = str_replace('${'.$key_chain.'.'.$key.'}',$value,$string);
            }            
        }
        
        return $string;
    } 
    
    /**
     * Replace application environmental parameters by its value
     * Environmental parameters in given string must be in below format: ${param_name}
     * @param   $string: input string;
     * @return  parsed string.
     * */
    public function parse_params($string)
    {           
        if ($string == '')
        {
            return;
        }
                
        return $this->_parse($string,$this->vars);
    }
}
/* End of file*/