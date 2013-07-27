<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Expression
{
    //const pattern_var = '\{[a-zA-Z_][a-zA-Z0-9_\.]*\}';
    const pattern_var = '[a-zA-Z0-9_][a-zA-Z0-9_\.]*';
    const pattern_string = '\'.*\'';
    const pattern_number = '\-?[0-9]+';
    const pattern_constant = 'TRUE|FALSE|NULL';
    
    private $_expression;
    private $invalid_value = FALSE;
    private $blank_value = TRUE;
    
    public function __construct()
    {        
        $pattern = self::pattern_var.'|'.self::pattern_string.'|'.self::pattern_number.'|'.self::pattern_constant;
        $this->_expression = "\(*($pattern)\s*(=|==|<>|!=|>|<|>=|<=)\s*($pattern)\)*";
    }
    
    public function invalid_value($input)
    {
        $this->invalid_value = $input;        
    }
    
    public function blank_value($input)
    {
        $this->blank_value = $input;        
    }    
    
    /**
     * Validate an expression.
     * */
    public function validate($expression)
    {
        // Validate expression
        //$valid = preg_match('/^\(*\s*'.$this->_expression.'(\s*\)*\s*(and|or|&|&&|\||\|\|)\s*\(*\s*'.$this->_expression.'\)*)*$/i',$expression);
        $pattern = $this->_expression.'(\s*(and|or|&|&&|\||\|\|)\s*'.$this->_expression.')*';
        $valid = preg_match("/^$pattern$/i",$expression);
        $pattern = self::pattern_var.'|'.self::pattern_string.'|'.self::pattern_number.'|'.self::pattern_constant;
        $valid = $valid || preg_match("/^$pattern$/i",$expression);
        
        if ( ! $valid)
        {
            write_log('error',"Invalid expression: ".$expression,'core');
        }
        return $valid;
    }
    
    /**
     * Convert an expression to PHP executable format
     * */
    public function standarize($expression)
    {
        //Replace new line character by space.
        $newline = array("\r\n", "\n", "\r");
        $expression = trim(str_replace($newline,' ',$expression));
        
        ///////////////////////////////////////
        //Convert to PHP executable format  
        $pattern = self::pattern_var.'|'.self::pattern_string.'|'.self::pattern_number.'|'.self::pattern_constant;
        
        //$pattern_exclude_var = self::pattern_string.'|'.self::pattern_number.'|'.self::pattern_constant;
        /*
        //------Add coma where needed---------        
        $expression = preg_replace("/(".$pattern_exclude_var.")/","{_$1}",$expression);
        $searches = array(                                
                            "/(".self::pattern_var.") *(=|==|<>|!=|>|<|>=|<=) *(\{_(".$pattern_exclude_var.")\})/",
                            "/(\{_(".$pattern_exclude_var.")\}) *(=|==|<>|!=|>|<|>=|<=) *(".self::pattern_var.")/",
                            "/(".self::pattern_var.") *(=|==|<>|!=|>|<|>=|<=) *(".self::pattern_var.")/"           
                            );

        $replacements = array(
                            "'$1' $2 $3",
                            "$1 $2 '$3'",
                            "'$1' $2 '$3'"
                            );        
        do
        {                                                   
            $expression = preg_replace($searches,$replacements,$expression,-1,$count);   
            write($expression);
        }while ($count);
        $expression = preg_replace("/\{_(".$pattern_exclude_var.")\}/","$1",$expression);        
        
         * 
         */
        //------Replace friendly operators to PHP ones---------
        $searches = array(
                            "/($this->_expression) *(or|\|) *($this->_expression)/i",
                            "/($this->_expression) *(and|&) *($this->_expression)/i",
                            "/($pattern) *= *($pattern)/",
                            "/($pattern) *<> *($pattern)/"
                            );

        $replacements = array(         
                            "$1 || $6",
                            "$1 && $6",
                            "$1 == $2",
                            "$1 != $2"         
                            );         
        do
        {                                                          
            $expression = preg_replace($searches,$replacements,$expression,-1,$count);                                    
        }while ($count);              

        return $expression;    
    }  
    
    /**
     * Evaluate given expression using parmeter in $obj_evr_param
     * @param   $expression: expression to be evaluated
     * @param   $parameters: data array or object to parse $expression
     * @return  this invalid_value setting if expression is invalid.
     *          this blank_value setting if expression is blank (empty)
     *          otherwise, parse environmental to expression and evaluate for value.
     * */
    public function evaluate($expression)
    {                               
        $expression = trim($expression);
        
        write_log("debug","Expression evaluating \n".highlight_info($expression),'core');        
        if ($expression == '')
        {
            return $this->blank_value;
        }
        
        if ( ! $this->validate($expression))
        {
            return $this->invalid_value;
        }
        
        $expression = $this->standarize($expression);        
                
        $return = @eval("return $expression;");
        write_log("debug","Evaluate (".highlight_info($expression)."). Return ".highlight_info(($return)?'TRUE':'FALSE'),'core');
        return $return;
    }
}
/* End of file*/