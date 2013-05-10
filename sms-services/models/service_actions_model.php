<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service actions class */
class Service_actions_model extends CI_Model
{
    //public $list;
    
    public $cache = array();
    
    //constructor
    public function __construct()
    {
        parent::__construct();         
    }
    
    /**
     *Get list of service actions which is child of $parent_id (only children)
     * @param integer parent_id.
     * @return array of row object
     */
    public function get_children_list($parent_id = 0)
    {        

        if ( ! isset($this->cache['children'][$parent_id]))
        {
            $this->db->from('service_actions')
                    ->where('parent_id',$parent_id)
                    ->where('active','yes')
                    ->order_by('precedence');
            $query = $this->db->get();
            $this->cache['children'][$parent_id] = $query->result_array();
            //if ($query->num_rows() == 0) return array();            
        }
        
        return $this->cache['children'][$parent_id];
    }
    
    /**
     *Get action from database base on id
     * @param interger $id
     * @return boolean 
     */
    public function get_action($id)
    {
        if ($id == 0)
        {
            return array(
                'id' => 0,
                'title' => 'Root',
                'description' => '',
                'type' => 'group',
                'input' => '',
                'parent_id' => NULL,
                'precedence'=>0,
                'expression' => NULL,
                'active'=>'yes'
            );
        }
        
        if (! isset($this->cache['action'][$id]))
        {
            $this->db->from('service_actions')
                    ->where('id',$id)
                    ->where('active','yes');
            $query = $this->db->get();            
            $this->cache['action'][$id] = ($query->num_rows() == 0)?FALSE:$query->row_array();
        }                
        return $this->cache['action'][$id];
    }
}
/*End of file*/
