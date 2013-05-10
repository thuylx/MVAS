<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
class Authors_model extends Base_module_model {
 
    function __construct()
    {
        parent::__construct('m_authors');
    }
}
 
class Author_model extends Base_module_record {
 
}