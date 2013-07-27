<?php
class Ctrl_service_actions extends CI_Controller {
     
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
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
     
    function copy($id = NULL)
    {
        if ($id){
            $this->db->from('service_actions')->where('id',$id);
            $query = $this->db->get();
            if ($query->num_rows() == 0)
            {
                $row = array();
            }
            else
            {
                $row = $query->row_array();
            }
        }
        else {
            $row = $this->input->post();            
        }
        
        if (! $row)
        {
            echo "Action not found, copy failed <br><br>";
        }
        else
        {
            $row['precedence'] += 1;        
            $id = $this->_copy($row);
            echo "Action '#".$row['id'].': '.$row['title']."' and its children copied.<br><br>";
        }                        
        
        $this->load->helper('url');        
        echo anchor('/fuel/service_actions','See service actions list<br><br>');
        echo anchor('/fuel/service_actions/edit/'.$id,'Go to newly added item');
    }
        
}