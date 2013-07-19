<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Udf_viettel extends MY_Controller {
        
    private $code;
    //private $sconfig;
    public function __construct()
    {
        parent::__construct();     
        //$this->sconfig = $this->load->config('udf',TRUE);        
        $this->load->model("Result_model");
        $this->load->model("Lottery_model");
    }
    
    /*****************************************************
     * Pre process MO before processing below scenario
     * Called automatically by core class, suitable for 
     *  standalize argument in MO content before processing
     *****************************************************/    
    public function __initialize()
    {
        //***************************************************************************
        // CHUAN HOA DU LIEU DAU VAO
        //***************************************************************************
        // Tin sau chuan hoa phai co dinh dang nhu vi du sau, tuy theo nhan kq ve nhu the nao phai
        // chuan hoa cho phu hop:
        // [A-Z]{2,3} +[0-9]{1,2}[\/\-][0-9]{1,2} + (= <ma tinh> <ngay thang>)
        // ( +([1-9]|DB) *: *([0-9]+( *- *[0-9]+)*))+ (= lien tiep cac cap <giai>:<kq1>-<kq2> ... co the co dau cach)
        // .* (=doan quang cao bat ky them vao cuoi ko care)
        
        $this->MO->argument = strtoupper($this->MO->argument);
        
        $searches = array(
                            '/DB:\.\.\./', //Loai bo phan 'DB:...' tin giai 7 XSMB cua 8502
//                            '/^DNA\s/', //Tin cap nhat tu mot so CP co ma cho Da nang la DNA, can chuyen ve DNG
//                            '/^QNA\s/',
//                            '/^DL\s/', //Tin cap nhat tu mot so CP dung ma DL (Da Lat), can chuyen ve LD (Lam Dong)
//                            '/^BTR\s/', //Ben tre, thay bang BT
                            '/([A-Z]{2,3} [0-9]{1,2}\/[0-9]{1,2}):\s/', //Loai bo dau : sau ngay thang tin XSMN tu 6289
                            '/\sDB6:([0-9]+)/' //Thay the DB6 bang DB o tin kq XSMN tu 6289
                            );
        $replacements = array(
                            '',
//                            'DNG ',
//                            'QNM ',
//                            'LD ',
//                            'BT ',
                            '$1 ',
                            ' DB:$1'
                            );
                                    
        do
        {
            $this->MO->argument = preg_replace($searches,$replacements,$this->MO->argument,-1,$count);                       
        }while ($count);                
    }
    
    /*****************************************************
     * Default service scenario     
     *****************************************************/     
    protected function sc_default()
    {        
        if ( ! $this->Result_model->parse_result_string($this->MO->argument))
        {
            write_log('error',"Parse lottery result string failed.");
            $this->MO->status = 'failed';
            $evr['updated'] = FALSE;
            $this->Evr->lottery = $evr;
            return FALSE;
        }
        $evr = array();
        $evr['code'] = $this->Result_model->code;
        $prize = $this->Result_model->get_max_prize();              
        $evr['prize'] = $prize;
        
        $updated = $this->Lottery_model->is_cached($this->Result_model->code,$prize,$this->Result_model->date);                
        if ($updated)
        {
            write_log('error',"Lottery Prize updated already, discarded MO id=".$this->MO->id);
            $this->MO->status = 'discarded';
            $evr['updated'] = FALSE;
            $this->Evr->lottery = $evr;
            return FALSE;
        }        
        
        $result = $this->Result_model->generate_result_string();
        $this->Lottery_model->cache($this->Result_model->code,$prize,$result);
        $evr['result'] = $result;      
        
        if ($prize=='DB')
        {
            //Cache loto result
            $loto = $this->Result_model->generate_loto_string();            
            $this->Lottery_model->cache($this->Result_model->code,'loto',$loto);
            
            //Update lottery result
            $this->Result_model->insert();
            
            //Store environment param for other services
            $evr['loto'] = $loto;
        }
        
        $evr['updated'] = TRUE;        
        
        //Get region information for service XSMN, XSMT
        $evr['region'] = $this->Lottery_model->get_region($this->Result_model->code);
        
        $this->Evr->lottery = $evr;
        
        $this->Event->raise("after_udf");
                                      
        return TRUE;
    }
}

/* End of file*/