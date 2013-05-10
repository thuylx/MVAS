<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Service controller class */
class Statistic_model extends CI_Model
{
    public $total_processed_mo = 0;
    public $total_sent_mt = 0;
        
    private $statistic = array();
    
	//constructor    
	public function __construct()
    {
        parent::__construct();
        //$this->load->helper('array');
    }    
    
    /**
     * Load customer information from database
     * @param   $msisdn: phone number
     * @return  database row(price,revenue) object, FALSE if customer not found
     * */
    public function cal_revenue($short_code, $smsc_id)
    {        
        $this->db->select('price,`price`*`proportion` AS revenue')
                     ->from('short_code')
                     ->join('telco_revenue_share','short_code.short_code = telco_revenue_share.short_code')
                     ->join('telco_smsc','telco_smsc.telco_id = telco_revenue_share.telco_id')
                     ->where('m_short_code.short_code',$short_code)
                     ->where('telco_smsc.smsc_id',$smsc_id);
        $query = $this->db->get();
        if ($query->num_rows()===0)
        {
            return (object)array('price'=>0,'revenue'=>0);
        }
        else
        {            
            return $query->first_row();
        }
    }

    /**
     * Save statistic information to array to update statistic table in 
     * real_time_statistic mode
     * @param   $MO: MO message object
     * */    
    public function save_mo($MO)
    {
        write_log('debug',"Statistic model saves MO into cache",'core');
        $date = date("Y-m-d",$MO->time);
        if (isset($this->statistic[$date][$MO->smsc_id][$MO->short_code][$MO->keyword]['mo']))
        {
            $this->statistic[$date][$MO->smsc_id][$MO->short_code][$MO->keyword]['mo'] += 1;
            $this->statistic[$date][$MO->smsc_id][$MO->short_code][$MO->keyword]['revenue'] += $MO->revenue;
        }
        else
        {
            $this->statistic[$date][$MO->smsc_id][$MO->short_code][$MO->keyword]['mo'] = 1;
            $this->statistic[$date][$MO->smsc_id][$MO->short_code][$MO->keyword]['revenue'] = $MO->revenue;
        }        
    }

    /**
     * Save statistic information to array to update statistic table in 
     * real_time_statistic mode
     * @param   $MTs a MT object or array of MT object
     * */
    public function save_mt($MTs)
    {
        write_log('debug',"Statistic model saves sent MT into cache",'core');
        if (! is_array($MTs))
        {
            $MTs = array($MTs);
        }
        
        foreach($MTs as $MT)
        {
            $date = date("Y-m-d",$MT->time);
            if (isset($this->statistic[$date][$MT->smsc_id][$MT->short_code][$MT->keyword]['mt']))
            {                
                $this->statistic[$date][$MT->smsc_id][$MT->short_code][$MT->keyword]['mt'] += 1;
            }
            else
            {
                $this->statistic[$date][$MT->smsc_id][$MT->short_code][$MT->keyword]['mt'] = 1;
            }
            
        }        
    }     
    
    /**
     * Update statistic from stored information to database
     * */    
    public function update()
    {                
        write_log('debug',"Statistic model update stored statistic",'core');      
        
        $table = $this->db->protect_identifiers('statistic',TRUE);
        $temp = array(); //Used to reset key array in below loop
        $temp[] =  $this->db->protect_identifiers('date');
        $temp[] =  $this->db->protect_identifiers('smsc_id');
        $temp[] =  $this->db->protect_identifiers('short_code');
        $temp[] =  $this->db->protect_identifiers('keyword');        
          
        foreach($this->statistic as $date=>$smscs)
        {
            foreach($smscs as $smsc_id=>$short_codes)
            {
                foreach($short_codes as $short_code=>$keywords)
                {
                    foreach($keywords as $keyword=>$amount)
                    {
                        if (count($amount) == 0)
                        {
                            write_log('error',"[WARNING] Statistic amount is an empty array, check the use of Statistic model!",'core');
                            break;
                        }                                                
                        
                        $keys = $temp;$values = array();$updates = array();
                        $values[] = $this->db->escape($date);
                        $values[] = $this->db->escape($smsc_id);
                        $values[] = $this->db->escape($short_code);
                        $values[] = $this->db->escape($keyword);
                                                                        
                        foreach ($amount as $key => $value)
                        {
                            $key = $this->db->protect_identifiers($key);
                            $value = $this->db->escape($value);
                            $keys[] = $key;
                            $values[] = $value;
                            $updates[] = "$key = $key + $value";
                        }                                                
                        
                        $sql =  "INSERT INTO $table (".implode(', ',$keys).")".
                                " VALUES (".implode(', ',$values).")".
                                " ON DUPLICATE KEY UPDATE ".implode(', ',$updates).";";
                                
                        $this->db->query($sql);
                                               
                        // Check to see if the query actually performed correctly
                        if ($this->db->affected_rows() == 0) 
                        {
                            write_log("error","Cannot update statistic for MT message",'core');
                        }

                    }                    
                }
            }
        }
    }        
    

    
}
/*End of file*/