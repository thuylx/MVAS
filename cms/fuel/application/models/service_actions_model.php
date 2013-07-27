<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Service_actions_model extends Base_module_model {
        
    //public $foreign_keys = array('service'=>'service_catalogues_model');
    public $required = array('title');
    //public $filters = array('id');
    public $auto_encode_entities = FALSE; //Not work somehow
    
    private $root_id = 0;
    private $level = 0;            
     
    function __construct()
    {
        parent::__construct('m_service_actions');     
        ini_set('memory_limit', '128M'); // Increase allowed memory size since the list might be huge   
        $this->uri->init_get_params();        
        if ( $this->input->get('root')) 
        {
            $_POST['root'] = $this->input->get('root');        
        }
        
        if ($this->input->post('root_tree') != '')
        {
            $_POST['root'] = $this->input->post('root_tree');
        }
        elseif($this->input->post('root') != '')
        {
            $_POST['root_tree'] = $this->input->post('root');
        }
    }        

    /*
    function get_full_shorted_list()
    {
        $data = array();
        $this->add_items_recursive($data, array(), 0,FALSE);
        return $data;
        
        
        $this->db->select('id,parent_id,title,precedence')
                 ->from('service_actions')
                 ->order_by('parent_id','asc')
                 ->order_by('precedence','asc');
        $query = $this->db->get();    
        $data = array();
        foreach($query->result() as $row)
        {
            $data[$row->id] = array('id'=>$row->id,'parent_id' => $row->parent_id,'title' => $row->title,'precedence'=>$row->precedence);
            if ($row->parent_id == 0)
            {
                $data[$row->id]['level'] = 0;
                $data[$row->id]['short_string'] = $data[$row->id]['precedence'];                
            }
            else
            {
                $data[$row->id]['level'] = '';
                $data[$row->id]['short_string'] = '';                
            }
            
        }
        
        $updated = TRUE;
        while ($updated)
        {
            $updated = FALSE;
            foreach($data as $key=>$value)
            {
                if (($value['level'] === '') && ($data[$value['parent_id']]['level'] !== ''))
                {
                    $delimiter = ($data[$value['parent_id']]['short_string']=='')?'':',';
                    $data[$key]['short_string'] = $data[$value['parent_id']]['short_string'].$delimiter.str_pad($value['precedence'],3,'0',STR_PAD_LEFT).'_'.str_pad($value['parent_id'],3,'0',STR_PAD_LEFT);
                    $data[$key]['level'] = $data[$value['parent_id']]['level'] + 1;
                    $updated = TRUE;
                }        
            }
        }        
        
        foreach($data as $key=>$value)
        {
            $short_strings[$key] = $value['short_string'];
        }
        array_multisort($short_strings, SORT_ASC, $data);
        foreach($data as $row)
        {
            $id = $row['id'];
            unset($row['id']);
            $return[$id] = $row;
        }        
        return $return;
    }
     * 
     */
    
    /**
     *Function add service action and its children into array of action recursively
     * @param array $data which actions to be added
     * @param array $row service action row (array)
     * @param interger $level The action stored in $row will not be added if $level is greater than the level selected in search form
     * @param boolean $force if TRUE then action and its children will be added regardless they match to search criterials or not.
     * @param string $tree_line used to draw tree in list view. Discard this if $tree_line = FALSE
     * @param array $excluded_root_actions array of actions id which and children of which will be not loaded
     * @return boolean 
     */
    function add_items_recursive(&$data, $row, $level=1, $force=FALSE, $tree_line='', $excluded_root_actions = NULL)
    {            
        if (!$row)
        {
            $row['id'] = 0;
            $row['title'] = 'Root';
        }
        
        if ($row['title'] != 'Root')
        {
            $search_term = $this->input->post('search_term');
            $found = ($search_term == '' || $search_term ==  'Enter search term...');
            $search_term = strtolower($search_term);
            $found = $found || ((strpos(strtolower($row['id']),$search_term) !== FALSE) || (strpos(strtolower($row['title']),$search_term) !== FALSE) || (strpos(strtolower($row['input']),$search_term) !== FALSE));            
            $force = $force || $found;
        }
                
        if ($this->level == 0 || $level+1<=$this->level) 
        {
            $this->db->select('id,title,type,input,expression,id as ID,active,precedence, parent_id')
                    ->from('service_actions') 
                    ->where('parent_id',$row['id'])                    
                    ->order_by('precedence','desc');
            if ($excluded_root_actions) $this->db->where_not_in('id',$excluded_root_actions);
            $query = $this->db->get();                    
            $children = $query->result_array();            
            for($i=0;$i<count($children);$i++)
            {
                $next_tree_line = $tree_line;
                if ($next_tree_line !== FALSE)
                {
                    $next_tree_line = ($i == 0)?'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;':':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $next_tree_line = $tree_line.$next_tree_line;
                }
                if ($this->add_items_recursive($data, $children[$i], $level+1,$force,$next_tree_line,$excluded_root_actions))
                {
                    $found = TRUE;
                }
            }            
        }
        
        if ($row['title'] != 'Root' && ($force || $found))
        {            
            if ($tree_line !== FALSE)
            {
                $searchs = array('/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$/','/:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$/');
                $tree_line = preg_replace($searchs, ':...&nbsp;', $tree_line);
                $row['title'] = $tree_line.$row['title'];
            }
            $data = array_merge(array($row),$data);                      
        }
       if ($row['title'] != 'Root')  return $found;
       return TRUE;
    }
    
    /*
    function _copy($action)
    {
        $id = $action['id'];
        unset($action['id']);
        
        $this->db->insert('service_actions',$action);
        $insert_id = $this->db->insert_id();
        
        //Copy children
        $this->db->from('service_actions')->where('parent_id',$id);
        $query = $this->db->get();
        foreach ($query->result_array() as $row)
        {
            $row['parent_id'] = $insert_id;
            $this->_copy($row);
        }
        
        return $insert_id;
    }
    
    function copy($row)
    {
        $row['precedence'] += 1;        
        $id = $this->_copy($row);                
    }    
     * 
     */        

    
    function list_items() {
        unset($this->filters['root']);
        unset($this->filters['level']);
        unset($this->filters['root_tree']);        
        
        $this->data_table->add_action('EDIT', 'service_actions/edit/{id}');        
        $this->data_table->add_action('EXPLORE', fuel_url('service_actions?root={id}'));
        $this->data_table->add_action('COPY', fuel_url('ctrl_service_actions/copy/{id}'));
        //$this->data_table->add_action('EDIT', 'service_actions/edit/{id}');        
        
        $this->root_id = ($this->input->post('root') != '')?(int)$this->input->post('root'):0;
        $this->level = ($this->input->post('level') != '')?(int)$this->input->post('level'):0;
        
        $data = array();         
        $this->add_items_recursive($data, array('id' => $this->root_id, 'title' => 'Root'), 0,FALSE,'');
        
        return $data;
    }
    
    function tree()
    {    
        $this->root_id = ($this->input->post('root') != '')?(int)$this->input->post('root'):0;
        $this->level = 0;
        
        $actions = array();         
        $this->add_items_recursive($actions, array('id' => $this->root_id, 'title' => 'Root'), 0,FALSE,FALSE);                
        
        $return = array();
        foreach($actions as $action)
        {
            //if ($action['parent_id'] == $this->root_id) $action['parent_id'] = 0;
            $title = $action['id'].' - '.$action['type']."\n".$action['input'];
            $title = ($action['expression'])?$title."\n".  str_pad('', 2*strlen($action['expression']),"_")."\nEXP: ".$action['expression']:$title;
            $attributes = ($action['active'] == 'no') ? array('class' => 'unpublished', 'title' => 'inactive - '.$title) : array('title' => $title);            
            $return[] = array('id' => $action['id'], 'label' => $action['title'], 'parent_id' => $action['parent_id'], 'location' => fuel_url('service_actions/edit/'.$action['id']), 'attributes' => $attributes);             
        }     
        
        $actions = array();
        $this->add_parent_recursive($actions, $this->root_id);
        foreach($actions as $action)
        {
            $title = $action['id'].' - '.$action['type']."\n".$action['input'];
            $attributes = ($action['active'] == 'no') ? array('class' => 'unpublished', 'title' => 'inactive - '.$title) : array('title' => $title);            
            $return[] = array('id' => $action['id'], 'label' => '<b><i>'.$action['title'].'</i></b>', 'parent_id' => $action['parent_id'], 'location' => fuel_url('service_actions/edit/'.$action['id']), 'attributes' => $attributes);
        }
        
        return $return;
    }
    
    function options_list($key = NULL, $val = NULL, $where = array(), $order = TRUE) {
        //parent::options_list($key, $val, $where, $order);
        //Do nothing to speed up form loading
    }
    
    function add_parent_recursive(&$data, $id)
    {
        $this->db->select('id,title,type,input,expression,id as ID,active,precedence, parent_id')
                ->from('service_actions')
                ->where('id',$id);
        $query = $this->db->get();
        if ($query->num_rows() == 0) return FALSE;
        $row = $query->row_array();
        
        if ($row['parent_id'] != 0) $this->add_parent_recursive($data, $row['parent_id']);
        
        $data[] = $row;
        return TRUE;
    }
    
    function get_options_list_path($id)
    {
        $data = array();
        $this->add_parent_recursive($data, $id);        
        
        $options = array();
        
        $line = ':....';
        foreach ($data as $item)
        {
            $options[$item['id']] = $line.'&nbsp;'.$item['title'];
            $line .= $line;
        }
        return $options;
    }
    
    
    //function options_list($key = NULL, $val = NULL, $where = array(), $order = TRUE) {        
    function get_options_list($max_level = 0, $excludes = array()) {                
        $list = array();        
        $this->level = $max_level;
        $this->add_items_recursive($list, array(), 0, FALSE,'',$excludes);        
        
        $options[0] = '- Root -';
        foreach($list as $key=>$value)
        {            
            $options[$value['id']] = $value['title'];
        }        
        return $options;
    }
    
    function form_fields($values = array())
    {        
        $fields = parent::form_fields($values);                          
        $excludes = array();
        if (isset($values['id'])) $excludes[] = $values['id'];                
        $options[0] = '- Root -';
        $options = $this->get_options_list(0,$excludes);                
        $fields['parent_id'] = array('type' => 'select', 'options' => $options);        
        $fields['input']['class'] = 'no_editor';
        //$fields['description']['class'] = 'no_editor';
        return $fields;
    }        
    
    function on_before_delete($where)
    {        
        $data = array();
        $this->add_items_recursive($data, array('id'=>$where['id'],'title'=>'Root'));
        $deletion = array();
        foreach ($data as $item)
        {
            $deletion[] = $item['id'];
        }        
        if ($deletion)
        {
            return $this->db->where_in('id',$deletion)->delete('service_actions');
        }
    }
}
 
class Service_action_model extends Base_module_record {

}