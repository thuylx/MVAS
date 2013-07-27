<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
class Articles_model extends Base_module_model {
    
    //public $foreign_keys = array('author_id' => 'authors_model');
    //public $parsed_fields = array('content', 'content_formatted');    
    //public $filters = array('author_id');
 
    function __construct()
    {
        parent::__construct('m_articles');
//        $author_options = array(2=>'Le Cong Trung',1=>'Le Xuan Thuy');
//        $this->filters = array('author_id' => array('label' => 'Author:', 'type' => 'select', 'options' => $author_options));
    }
     
    function list_items($limit = NULL, $offset = NULL, $col = 'name', $order = 'asc')
    {
        $this->db->join('authors', 'authors.id = articles.author_id', 'left');
        $this->db->select('articles.id, authors.name AS author, title, content, date_added, articles.order, articles.published');        
        $data = parent::list_items($limit, $offset, $col, $order);        
        $return = array();
        foreach ($data as $row)
        {
            $row['content'] = substr($row['content'],0,50);
            $return[] = $row;
        }
        return $return;
    }
    
    function form_fields($values = array())
    {

        // ******************* NEW RELATED CATEGORY FIELD BEGIN *******************
        $related = array('categories' => 'categories_to_articles_model');
        // ******************* NEW RELATED CATEGORY FIELD END *******************

        $fields = parent::form_fields($values, $related);

        $CI =& get_instance();
        $CI->load->model('authors_model');
        //$CI->load->model('categories_model');
        //$CI->load->model('categories_to_articles_model');

        $author_options = $CI->authors_model->options_list('id', 'name', array('published' => 'yes'));
        $fields['author_id'] = array('type' => 'select', 'options' => $author_options);        
        return $fields;
    }
    
    function on_after_save($values)
    {
        $data = (!empty($this->normalized_save_data['categories'])) ? $this->normalized_save_data['categories'] : array();
        $this->save_related('categories_to_articles_model', array('article_id' => $values['id']), array('category_id' => $data));
    }
    
    function tree()
    {
        $CI =& get_instance();
        $CI->load->model('categories_model');
        $CI->load->model('categories_to_articles_model');

        $return = array();
        $categories = $CI->categories_model->find_all(array(), 'id asc');
        $categories_to_articles = $CI->categories_to_articles_model->find_all('', 'categories.name asc');

        $cat_id = -1;
        foreach($categories as $category)
        {
            $cat_id = $category->id;
            $return[] = array('id' => $category->id, 'label' => $category->name, 'parent_id' => 0, 'location' => fuel_url('categories/edit/'.$category->id));
        }
        $i = $cat_id +1;

        foreach($categories_to_articles as $val)
        {
            $attributes = ($val->published == 'no') ? array('class' => 'unpublished', 'title' => 'unpublished') : NULL;
            $return[$i] = array('id' => $i, 'label' => $val->title, 'parent_id' => $val->category_id, 'location' => fuel_url('articles/edit/'.$val->article_id), 'attributes' =>  $attributes);
            $i++;
        }
        return $return;
    }
}
 
class Article_model extends Base_module_record {
 
}