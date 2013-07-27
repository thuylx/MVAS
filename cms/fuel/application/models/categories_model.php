<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(FUEL_PATH.'models/base_module_model.php');
 
class Categories_model extends Base_module_model {
     
    public $record_class = 'Category';
     
    function __construct()
    {
        parent::__construct('m_categories');
    }
 
     
    // cleanup category to articles
    function on_after_delete($where)
    {
        $CI =& get_instance();
        $CI->load->model('categories_to_articles_model');
        if (is_array($where) && isset($where['id']))
        {
            $where = array('category_id' => $where['id']);
            $CI->categories_to_articles_model->delete($where);
        }
    }
    
    function tree()
    {        
        //$CI =& get_instance();
        //$CI->load->model('categories_model');
        
        $categories = $this->find_all(array(), 'id asc');
        //$categories_to_articles = $CI->categories_to_articles_model->find_all('', 'categories.name asc');
        
        //$cat_id = -1;
        foreach($categories as $category)
        {
            //$cat_id = $category->id;
            $return[] = array('id' => $category->id, 'label' => $category->name, 'parent_id' => $category->parent_id, 'location' => fuel_url('categories/edit/'.$category->id));
        }
        return $return;
    }
}
 
class Category_model extends Base_module_record {
}