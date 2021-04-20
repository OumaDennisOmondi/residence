<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SMSController extends Controller
{
    //

    public function sendSMS($msg,$phone){

        $url = 'https://sms.textsms.co.ke/api/services/sendsms/';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); //setting custom header
      
      
        $curl_post_data = array(
                //Fill in the request parameters with valid values
               'partnerID' => '2988',
               'apikey' => '3b8d9664c40559746892dedece243caf',
               'mobile' => strval($phone),
               'message' => $msg,
               'shortcode' => 'PV_Tech',
               'pass_type' => 'plain', //bm5 {base64 encode} or plain
        );
      
        $data_string = json_encode($curl_post_data);
      
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
      
        $curl_response = curl_exec($curl);
       // print_r($curl_response);
    }
}
