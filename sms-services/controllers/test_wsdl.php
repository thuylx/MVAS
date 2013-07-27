<?php
class Test_wsdl extends MX_Controller {
    function __construct() {
        parent::__construct();
        
        //$this->load->library("nusoap");
        require_once(APPPATH.'libraries/nusoap/nusoap'.EXT); //includes nusoap
        
        $this->nusoap_server = new nusoap_server();
        $this->nusoap_server->configureWSDL("SendSMS", "urn:SendSMS");
        
        $this->nusoap_server->wsdl->addComplexType(
            "MT",
            "complexType",
            "array",
            "",
            "SOAP-ENC:Array",
            array(
                "mo_id"=>array("name"=>"mo_id", "type"=>"xsd:int"),
                "short_code"=>array("name"=>"short_code", "type"=>"xsd:string"),
                "content"=>array("name"=>"content", "type"=>"xsd:string"),
                "msisdn"=>array("name"=>"msisdn", "type"=>"xsd:string"),
                "type"=>array("name"=>"type", "type"=>"xsd:int"),
                "link"=>array("name"=>"link", "type"=>"xsd:string"),
                "smsc"=>array("name"=>"smsc", "type"=>"xsd:string")                
            )
        );    
                
        $this->nusoap_server->register(
            "sendSMS",
            array('param'=>"tns:MT"),
            array("return"=>"tns:int"),
            "urn:SendSMS",
            "urn:SendSMS#sendSMS",
            "rpc",
            "encoded",
            "Send MT message to user"
        );
    }
    
    function index() {
        // this just expose webservice's methods. if you put this in every method of the webservice to describe it you won't get it to work because of some post/get issues i guess


        // this is a workaround for not having get params enabled in ci
        // usually you get the nusoap webservice's wsdl by appending "?wsdl" at the end of its url
        //if($this->uri->segment(3) == "wsdl") {
        //    $_SERVER['QUERY_STRING'] = "wsdl";
        //} else {
        //    $_SERVER['QUERY_STRING'] = "";
        //}
        // Use the request to (try to) invoke the service
        $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents("php://input");
        $this->nusoap_server->service($HTTP_RAW_POST_DATA);        
        //$this->nusoap_server->service(file_get_contents("php://input"));
    }
    
    public function select_member_info() {
        function selectMemberInfo($member_id)
        {
            //$CI =& get_instance();
            //$CI->load->model("Member");
            
            //$CI->Member->_id = $member_id;
                        
            //$row = $CI->Member->selectMemberInfo(); // the method we use to retrieve member's info as array
            $row = array(
                'id' => 1,
                'firstname' => 'tao day',
                'lastname' => 'chu ai'
            );
            return $row;
        }        
        //$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
        //$this->nusoap_server->service($HTTP_RAW_POST_DATA);
        if (!isset($HTTP_RAW_POST_DATA)){
            $HTTP_RAW_POST_DATA = file_get_contents('php://input');
        }                        
        $this->nusoap_server->service($HTTP_RAW_POST_DATA); 
    }
    
    public function server()
    {
        
    }
}  