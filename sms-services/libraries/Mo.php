<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * MO message class
 * reserved fields:
 * - saved: whether saved to database or not.
 * - inserted: whether newly inserted to database or not.
 * - new_customer: whether or not MO was sent by a new customer.  
 * - delta_balance: amount balance have been changed if any.
 * */
class Mo
{
    private $CI;
    private $msg = array();
    
    //name of changed properties
    private $changed_properties = array();
    //name of savable properties
    private static $savable_properties = array(
                                                'id',
                                                'keyword',
                                                'argument',
                                                'msisdn',
                                                'content',
                                                'short_code',
                                                'time',
                                                'smsc_id',
                                                'status',
                                                'balance',
                                                'tag',
                                                'last_provision_time',
                                                'cancelled',
                                                'file_export_id'
                                            ); 

    //constructor
    public function __construct($data = NULL)
    {
        write_log('debug',"MO object constructing",'core');
        $this->CI =& get_instance();
        
        if ( ! is_null($data))
        {
            $this->load($data);
        }                                                                                                                                   
    }    
        
    public function &__get($key)
    {       
        if ( ! isset($this->msg[ $key ]))
        {
            $null = NULL;
            return $null;
        }
        
        return $this->msg[ $key ];        
    }
    
    public function __set($key,$value)
    {   
        if (( ! $this->is_changed($key)) &&  ($this->$key !== $value)) 
        {             
            $this->changed_properties[] = $key;            
        }                
        
        if ($key == 'balance')
        {                                    
            $this->msg['delta_balance'] = (isset($this->msg['delta_balance']))?$this->msg['delta_balance'] + ($value - $this->balance):($value - $this->balance);
            if ($value >= 0)
            {
                write_log('debug',highlight_info('<strong><em>Changed MO balance from '.(($this->is_set($key))?$this->$key:'NULL').' to '.$value.'</em></strong>'),'mo');                          
            }
            else
            {                
                write_log('error','<strong><em>WARNING: Balance changed from '.(($this->is_set($key))?$this->$key:'NULL').' to a negative number ('.$value.').</em></strong>','mo');        
            }                          
        }
        
        $this->msg[ $key ] = $value;
    }     
    
    // check if value is set
    public function is_set($key)
    {
        return isset($this->msg[ $key ]);
    }    

	// gets a value
	public function get($var)
	{
		return $this->msg[$var];
	}

	// sets a key => value
	public function set($key,$value)
	{
		$this->msg[$key] = $value;
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
					unset($this->msg[$var]);
				}
			}
			else
			{
				unset($this->msg[$vars]);
			}
		}
		else
		{
			$this->msg = array();
		}
	}
    
    public function reset()
    {
        $this->msg = array();
        $this->changed_properties = array();
    }
    
    
    /**
     * Clear changing mark
     * */
    public function clear_changes($property = NULL)
    {
        if ($property)
        {                        
            $key = array_search($property,$this->changed_properties);
            if ($key !== FALSE)
            {                                
                unset($this->changed_properties[$key]);                            
            }                        
            
        }
        else
        {
            $this->changed_properties = array();            
        }        
    }
        
    /**
     * Check whether a property has been changed after loading or not
     * @param   String $property: property title to be checked, if not set will see if any field changed.
     * @return  Boolean TRUE if the property has been changed, otherwise FALSE.
     * */                                    
    public function is_changed($property = '')
    {
        if ($property)
        {
            return in_array($property,$this->changed_properties);            
        }
        else
        {
            return (count($this->changed_properties)>0);
        }
    }
    
    /**
     * Check whether there is at least one savable property has been changed except a property of balance
     * since balance is not a native savable property     
     * @return  TRUE if the property has been changed, otherwise FALSE.
     * */
    public function is_savable_changed()
    {        
        return (count(array_intersect(self::$savable_properties,$this->changed_properties))>0);
    }
    
                
    
	/* return the object as an array */
    /**
     * Get properties array
     * @param   $savable: whether or not get only database savable fields
     * @param   $changed: whether or not get only fields which has been changed after loading.
     * @return  array of properties.
     * */    
	public function get_all_properties($savable = FALSE, $changed = FALSE)
	{
        if ( ! ($savable || $changed))
        {
            return $this->msg;           
        }        
        
        $keys = array_keys($this->msg);                
                     
        if ($changed)
        {            
            //$props = exist_elements($this->changed_properties,$props);
            $keys = array_intersect($this->changed_properties,$keys);         
        }
        
        if ($savable)
        {   
            //$props = exist_elements(self::$savable_properties,$props);
            $keys = array_intersect(self::$savable_properties,$keys);
        }   
                                
        $props = array();
        foreach($keys as $key)
        {
            $props[$key] = $this->msg[$key];
        }        
        
        return $props;
	}
        
    /**
     * Parse content string for keyword and arguments
     * This function will not change $this->content parameter.
     * @param   $mo_content: content string to be parsed, is $this->content if not set
     * @return  void
     * */
    public function parse_content($mo_content = NULL)
    {        
        write_log('debug',"MO parses content of '$mo_content'",'core');
        $mo_content = ($mo_content == '')?$this->content:$mo_content;                   
        $pos = strpos($mo_content," ");
        if ($pos === FALSE) //No argument
        {            
            $this->keyword = strtoupper($mo_content);                               
        }
        else
        {
            $this->keyword = substr($mo_content,0,$pos);
            $this->keyword = strtoupper($this->keyword);
            $this->argument = substr($mo_content,$pos+1,strlen($mo_content)-$pos);
        }
    }
    
    /**
     * Get MO properties from URI string
     * Format of URI like this:
     *  /controller/method/short_code/7927/msisdn/%2B841999890003/time/1301984593/smsc_id/GSM-Modem/content/sms+content
     * */
    public function parse_uri()
    {        
        write_log("debug","Parse URI for comming MO sms",'mo');
                
        $CI =& get_instance();
        $array = $CI->uri->ruri_to_assoc(3);
        
        array_walk($array,create_function('&$str','$str = urldecode($str);'));
        
        //Since function _filter_uri in system/libraries/URI.php convert from bad to good character in URI, so we have to do below action.
        //In additional, Kannel will convert ' to \' and \ to \\        
        $bad	= array('$', 		'(', 		')',	 	'%28', 		'%29',  	"'",   "\\");
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;',	"\'",  "\\\\");        
        $array['content'] = str_replace($good, $bad, $array['content']);
        $array['short_code'] = substr($array['short_code'],strlen($array['short_code'])-4); 
        
        $this->load($array);
        
        /*
        print_r($array);
        //***************************************************************************
        //Generate $MO message information                                
        $segments = $this->CI->uri->rsegment_array();
        $arr_ruri = array_slice($segments,2); //by pass controler and function name                             
        $arg = array_slice( $arr_ruri, 4 ); //all of segment after first 4 ones will be stored as $arr_ruri[6] as MO content        
        $arr_ruri[4] = implode('/', $arg);           
        for ($i=0;$i<=4;$i++)
        {
            $arr_ruri[$i] = urldecode($arr_ruri[$i]);
        }        
        $short_code = $arr_ruri[0];
        $short_code = substr($short_code,strlen($short_code)-4); 
                                 		
        //Since function _filter_uri in system/libraries/URI.php convert from bad to good character in URI, so we have to do below action.
        //In additional, Kannel will convert ' to \' and \ to \\
        $bad	= array('$', 		'(', 		')',	 	'%28', 		'%29',  	"'",   "\\");
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;',	"\'",  "\\\\");
		$arr_ruri[4] = str_replace($good, $bad, $arr_ruri[4]);                
        
        $this->load(array("short_code"=>$short_code,"msisdn"=>$arr_ruri[1],"time"=>$arr_ruri[2],"smsc_id"=>$arr_ruri[3],'content'=>$arr_ruri[4]));
        */     
        //$this->parse_content();           
        
        write_log('debug',"Got MO from URI:<br />".highlight_content(print_r($this->get_all_properties(),TRUE)),'mo');                                         
    }
    
    /**
     * Load MO from input array
     * @param   $data: array or object wich contain MO information
     * */
    public function load($data)
    {           
        if (is_object($data))
        {
            $data = get_object_vars($data);
        }
        
		foreach($data as $key=>$value)
		{
			$this->msg[$key] = $value;
		}                
    }    
    
    public function save()
    {        
        $this->CI->MO_Box->add($this);
        
        //reset object after sending
        $this->reset();
    }    
    
    /**
     * Auto correct keyword and arguement of the MO based on given rules and content or original content if this parameter not set
     * Rules is array of rule
     * Each rule is an array consist of 2 properties: 'pattern' and 'replacement'
     * This function will search for first rule which pattern match with MO content
     * and do preg replacement. Letter ^ alway inserted to begining of pattern before searching.
     * No more rule in will be processed once match found.
     * 
     * Update to MO keyword ad argument after correcting the content, 
     * always keep MO content property no change as it store original message
     * 
     * @param   $rules: array of rule.    
     * @return  void
     * */
    public function auto_correct($rules)
    {        
        write_log('debug',"MO do auto correction",'mo');
        
        if (! $rules)
        {
            return FALSE;
        }
        
        //Prepare
        $to_be_removed = array("\r\n", "\n", "\r", " ");
        
        if ($this->keyword)
        {
            $corrected_content = ($this->argument)?$this->keyword." ".$this->argument:$this->keyword;
        }
        else
        {                                               
            //$corrected_content = remove_accents($this->content);            
            $corrected_content = convert_accented_characters($this->content);         
            
            //$corrected_content = strtoupper($corrected_content);               
            
            //Remove Cross-Site Scripting attack
            $this->CI->load->helper('security');
            $corrected_content = xss_clean($corrected_content);            
            
            write_log('debug','Load original no accent content: '.highlight_content($corrected_content),'mo');
        }                 
        
        //Add a blank at the end to indicate that end of word.
        $corrected_content .= " ";
        
        //Scan rules for rule        
        foreach ($rules as $rule)
        {
            $pattern = $rule['pattern'];
            $replacement = $rule['replacement'];
            
            //Remove newline and space character.            
            $pattern = str_replace($to_be_removed,"",$pattern);
            //Replace space or underscore in pattern by none-accepted character pattern (Only A-Z, a-z, 0-9 accepted in keyword)                        
            $pattern = preg_replace('/[_]+/','[^A-Za-z0-9]',$pattern);                                    
                        
            $corrected_content = preg_replace('/^'.$pattern.'/i',$replacement,$corrected_content,-1,$count);            
            
            if ($count)
            {            
                write_log("debug","Apply rule:\n<em>".highlight_info($rule['pattern'])."</em>\nreplaced by <em>".highlight_info($rule['replacement'])."</em>\n->Corrected content = ".highlight_content($corrected_content),'mo');
                //Stop further processing
                break;
            }                   
        }           
        
        //Remove unused space if any
        $corrected_content = trim($corrected_content);
        $corrected_content = preg_replace('/\s{2,}/', ' ',$corrected_content);
                                       
        $this->parse_content($corrected_content);

        return $corrected_content;
    }     
}
/*End of file*/