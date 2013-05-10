<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CLASS MT Message
 * Reserved properties:
 * - no_signature: whether or not add signature before sending
 * - saved: whether or not the message has saved into database
 * - sent: whether or not the message has sent to Messaging Gateway
 * */
class Mt
{
    private $CI;
    private $msg = array('mo_id' => 0); //Set default for mo_id
    
    //name of changed properties
    private $changed_properties = array();
    //name of savable properties
    private static $savable_properties = array(
                                                'id',
                                                'mo_id',
                                                'short_code',
                                                'content',
                                                'udh',
                                                'msisdn',
                                                'time',
                                                'smsc_id',
                                                'service_action_id',
                                                'resend'
                                            );     

	//constructor
    public function __construct($data = NULL)
    {                
        write_log('debug',"MT object constructing",'core');
        
        $this->CI =& get_instance();                     
        
        if (! is_null($data))
        {
            if ( ! is_array($data) && is_string($data))
            {
                $data = array('content'=>$data);
            }
            $this->load($data);            
        }                

    }    
        
    public function __get($key)
    {       
        if ( ! isset($this->msg[ $key ]))
        {
            return NULL;
        }
        return $this->msg[ $key ];
    }
    
    public function __set($key,$value)
    {       
        if (( ! $this->is_changed($key)) && ($this->$key !== $value))
        {
            $this->changed_properties[] = $key;            
        }
        
        //Correct content
        if ($key == 'content')
        {
            $value = str_replace("\r\n","\n",$value);
            $value = str_replace("\r","\n",$value);            
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
    
    /**
     * Set mo_id, short_code, msisdn, smsc_id accordingly to MO message to reply
     * @param   $MO: MO object
     * */
    public function reply_to($MO)
    {        
        if (! is_object($MO))
        {
            return FALSE;
        }
        
        if ($MO->is_set('id'))
        {
            $this->mo_id = $MO->id;
        }
        
        if ($MO->is_set('msisdn'))
        {          
            $this->msisdn = $MO->msisdn;
        }
        
        if ($MO->is_set('short_code'))
        {
            $this->short_code = $MO->short_code;
        }        

        if ($MO->is_set('smsc_id'))
        {
            $this->smsc_id = $MO->smsc_id;
        }
        
        //This field is used for statistic feature.
        if ($MO->is_set('keyword'))
        {
            $this->keyword = $MO->keyword;
        }                          
    }
    
    //empties all of properies
    /**
     * Clear and reset data structure of object
     * */
    public function reset()
    {
        $this->msg = array();
        $changed_properties = array();
    }

    /**
     * Check whether a property has been changed after loading or not
     * @param   $property: property title to be checked, if not set will see if any field changed.
     * @return  TRUE if the property has been changed, otherwise FALSE.
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
     * Load MT from input array of properties
     * @param   $data: array or object which contain MT properties     
     * */
    public function load($data)
    {            
        if (is_object($data))
        {                        
            $data = (method_exists($data,'get_all_properties'))?$data->get_all_properties():get_object_vars($data);
        }
                        
		foreach($data as $key=>$value)
		{		  		  
            $this->msg[$key] = $value;         
		}                
    }        
    
    public function send($content = NULL)
    {
        if ($content)
        {
            $this->content = $content;
        }
                               
        $this->CI->MT_Box->add($this);
        
        //Reset MT after sending
        $this->reset();
    }    
    
    /**
     * WAP PUSH TO GATEWAY
     * @param   pap_template: xml template which presents PAP control.    
     * @param   si_template: xml template which presents SI (Service Indicator).
     * default files (pap_default.xml and si_default.xml) located in views/wap_push
     * */
    public function wap_push($pap_tmplate = 'default', $si_template = 'default')
    {
        if (! isset($this->msg['udh']) || $this->msg['udh'] == '')
        {
            return FALSE;
        }
        
        $this->CI->MT_model->insert($this);
        
        $PPG = $this->CI->config->item('PPG');
        $url  =     "http://".$PPG['host'].":".$PPG['port'];
        
        $this->CI->load->helper('xml');
        $data = array(
            'link'      => xml_convert($this->udh),
            'text'      => $this->content,
            'si_id'     => $this->id,
            'push_id'   => $this->id,
            'msisdn'    => $this->msisdn
        );
        
        $body  = "--multipart-boundary";
        $body .= "\r\n";
        $body .= "Content-type: application/xml";
        $body .= "\r\n\r\n";
        $body .= $this->CI->load->view("wap_push/pap_$pap_tmplate.xml",$data,TRUE);
        $body .= "\r\n";
        $body .= "--multipart-boundary";
        $body .= "\r\n";
        $body .= "Content-type: text/vnd.wap.si";
        $body .= "\r\n\r\n";        
        $body .= $this->CI->load->view("wap_push/si_$si_template.xml",$data,TRUE);
        $body .= "\r\n";
        $body .= "--multipart-boundary--";
                    
        $dlr_url = $this->CI->config->item('dlr_url','MT').$this->id;
        $dlr_mask = $this->CI->config->item('dlr_mask','MT');
        
        $post =     "POST ".$PPG['url']." HTTP/1.1\r\n".
                    "Host: ".$PPG['host'].":".$PPG['port']."\r\n".
                    "Authorization: Basic ".base64_encode($PPG['username'].":".$PPG['password'])."\r\n".
                    "X-Kannel-SMSC: $this->smsc_id\r\n".
                    "X-Kannel-From: $this->short_code\r\n".
                    "X-Kannel-DLR-URL: $dlr_url\r\n".   
                    "X-Kannel-DLR-Mask: $dlr_mask\r\n".                 
                    'Content-Type: multipart/related; boundary=multipart-boundary; type="application/xml"'."\r\n".
                    "Content-Length: ".strlen($body)."\r\n".
                    "\r\n".$body;
                                           
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt ($ch,CURLOPT_CUSTOMREQUEST , $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $reply = curl_exec($ch);
        curl_close ($ch);
        
        write_log('debug',highlight_info("<strong>WAP-PUSH from $this->short_code to $this->msisdn via $this->smsc_id:</strong>\n").highlight_content($this->content)."\n".  highlight_content($this->udh),'mt');
        $this->reset();
        
        write_log('debug','[WAP-PUSH] '.$reply,'mt');        
    }
}
/*End of file*/