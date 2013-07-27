<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mo_box
{        
    //Cache mo message to save after processing by service controller
    private $cache = array();
    
    private $max_cache_size = 1000;
    
	//constructor
	public function __construct()
    {
        write_log('debug',"MO_Box Constructing",'core');        
    }
    
    public function set_max_cache_size($size = 1000)
    {
        $this->max_cache_size = $size;
    }
    
    public function get_max_cache_size()
    {
        return $this->max_cache_size;
    }
    
    /**
     * Reset cache
     * */
    public function reset()
    {
        $this->cache = array();
    }
    
    /**
     * Add MO into cache
     * @param $MO: MO object to be add
     * */
    public function add($MO)
    {                     
        $this->cache[] = clone $MO;
        write_log('debug',"Added MO message id=$MO->id to cache",'core');
        
        if (count($this->cache) == $this->max_cache_size)
        {
            write_log('debug',"MO Box reaches max cache size, do provisioning to free up memory",'core');
            $this->process();
        }
        
    }
    
    /**
     * Process cached messages
     * - save to database
     * - reset cache
     * */
    public function process()
    {
        $CI =& get_instance();
        
        $CI->scp->process_mo_box();
    }            
    
    /**
     * Get array of cached MO object
     * @param   $saved: TRUE, FALSE or 'ALL'
     * @return  array
     * */
    public function get_cache($saved = 'ALL')
    {
        $return = array();
        foreach ($this->cache as $MO)
        {
            if($saved == 'ALL' || ($MO->saved == $saved))
            {
                $return[] = $MO;
            }
        }
        return $return;
    }
}
/* End of file */