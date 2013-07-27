<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "service";
$route['404_override'] = '';

//Routes for UDF which send from CPs for lottery result update
//XSMB
$route['service/exec/(:any)/(:any)/(:any)/(:any)/(([Bb][Nn]|[Hh][Pp]|[Nn][Dd]|[Qq][Nn]|[Tt][Bb]|[Tt][Dd])\+([0-9]{1,2})(-|%2F)([0-9]{1,2})((-|%2F)([0-9]{2,4}))?\+.*)'] = "service/exec/$1/$2/$3/$4/UDF+$5";

$lottery_codes = array(
    'AG'    => '[Aa][Gg]|A\.Giang',
    'BD'    => '[Bb][Dd]|B\.Duong',
    'BDI'   => '[Bb][Dd][HhIi]|B\.Dinh',
    'BL'    => '[Bb][Ll]|B\.Lieu',
    'BP'    => '[Bb][Pp]|B\.Phuoc',
    'BT'    => '[Bb][Tt][Rr]?|B\.Tre',
    'BTH'   => '[Bb][Tt][Hh]|B\.Thuan',
    'CM'    => '[Cc][Mm]|C\.Mau',
    'CT'    => '[Cc][Tt]|C\.Tho',
    'DLK'   => '[Dd][Ll][Kk]|D\.Lac|D\.Lak',
    'DN'    => '[Dd][Nn]|D\.Nai',
    'DNG'   => '[Dd][Nn][GgAa]|D\.Nang',
    'DNO'   => '[Dd][Nn][Oo]|D\.Nong',
    'DT'    => '[Dd][Tt]|D\.Thap',
    'GL'    => '[Gg][Ll]|G\.Lai',
    'HCM'   => '[Hh][Cc][Mm]|T\.Pho',
    'HG'    => '[Hh][Gg]|H\.Giang',
    'KG'    => '[Kk][Gg]|K\.Giang',
    'KH'    => '[Kk][Hh]|K\.Hoa',
    'KT'    => '[Kk][Tt]|K\.Tum',
    'LA'    => '[Ll][Aa]|L\.An',
    'LD'    => '[Ll][Dd]|[Dd][Ll]|D\.Lat',
    'NT'    => '[Nn][Tt]|N\.Thuan',
    'PY'    => '[Pp][Yy]|P\.Yen',
    'QB'    => '[Qq][Bb]|Q\.Binh',
    'QNG'   => '[Qq][Nn][Gg]|Q\.Ngai',
    'QNM'   => '[Qq][Nn][MmAa]|Q\.Nam',
    'QT'    => '[Qq][Tt]|Q\.Tri',
    'ST'    => '[Ss][Tt]|S\.Trang',
    'TG'    => '[Tt][Gg]|T\.Giang',
    'TN'    => '[Tt][Nn]|T\.Ninh',
    'TTH'   => '[Tt][Tt][Hh]|HUE',
    'TV'    => '[Tt][Vv]|T\.Vinh',
    'VL'    => '[Vv][Ll]|V\.Long',
    'VT'    => '[Vv][Tt]|V\.Tau'
);
foreach ($lottery_codes as $key => $value)
{
    $route['service/exec/(:any)/(:any)/(:any)/(:any)/('.$value.')\+(([0-9]{1,2})(-|%2F)([0-9]{1,2})((-|%2F)([0-9]{2,4}))?(%3A)?\+.*)'] = 'service/exec/$1/$2/$3/$4/UDF+'.$key.'+$6';
}

/* End of file routes.php */
/* Location: ./application/config/routes.php */