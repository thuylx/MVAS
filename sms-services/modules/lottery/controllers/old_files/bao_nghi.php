<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bao_nghi extends MY_Controller {
        
    private $sconfig;        
    private $date = NULL;
    private $lottery_code = 'MB';
    
    public function __construct()
    {
        parent::__construct();      
    }
    
    /*****************************************************
     * Default service scenario
     *****************************************************/     
    protected function sc_default()
    {                              
        $reply = "Trung tam xo so nghi tet Nguyen Dan ngay 30 va mung 1,2,3 tet.\nKinh chuc quy khach mot nam moi phat tai, an khang, thinh vuong.";
        $this->reply_string($reply);
    }
}

/* End of file*/