<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'candidates';

// Candidates routes
$route['candidates'] = 'candidates/index';
$route['candidates/change_status'] = 'candidates/change_status';
$route['candidates/upload_offer'] = 'candidates/upload_offer';
$route['candidates/verify_otp'] = 'candidates/verify_otp';
$route['candidates/resend_otp'] = 'candidates/resend_otp';
$route['candidates/view_offer/(:num)'] = 'candidates/view_offer/$1';
$route['translate_uri_dashes'] = FALSE;
