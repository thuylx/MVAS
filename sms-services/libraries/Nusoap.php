<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Nusoap
{
    public function __construct()
    {
        require_once(APPPATH.'libraries/nusoap/nusoap'.EXT); //includes nusoap
    }
}