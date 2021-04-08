<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\ResidentialAddress;
class UniversalAddressController extends Controller
{
    //
    public function __construct(){
        $this->middleware('auth', ['except' => ['home','view','action']]);
    }
    public function home(){
        //list business addresess that have been claimed
        $addresses = Address::where('claimed', 1)->get();
        return response()->json(['addresses' => $addresses],200);
    }
     public function view($address_id){
        $address_id = $address_id;
        $address=Address::where('address_id',$address_id)->first();
        if( !$address){
            return response()->json(['error' => 'Address not found'],404);
        } 
        return response()->json(['address'=> $address],200);
     }
    public function search_to_claim($location_id){
        if (stripos($location_id, 'B') !== false) {
            $addresses=Address::where('location_id',$location_id)->get();
            return response()->json(['address_type' => 'business', 'addresess'=> $addresses],200);
        }
        elseif(stripos($location_id, 'R') !==false){
            $addresses=ResidentialAddress::where('location_id',$location_id)->get();
            return response()->json(['address_type' => 'residential', 'addresess'=> $addresses],200);
        }
    }

    public function claim_address(Request $request){
        $location_id=$request->location_id;
        $address_id = $request->address_id;
        if (stripos($location_id, 'B') !== false) {
            $address=Address::where('address_id',$address_id)->first();
            
            //return response()->json(['address_type' => 'business', 'addresess'=> $addresses],200);
        }
        elseif(stripos($location_id, 'R') !==false){
            $address=ResidentialAddress::where('address_id',$address_id)->first();
           // return response()->json(['address_type' => 'residential', 'addresess'=> $addresses],200);
        }
        //return response()->json(['addresess'=> $address],200);
        if($address->claimed){
            return response()->json(['error'=> 'Address has already been claimed'],401);
        }
        $address->owner_id=auth()->user()->id;
        $address->claimed=true;
        $address->save();

        //send sms
        $ch = curl_init();
        $params=[
       'apiKey' => '120ca9639da262edc34804590b59cb40',
       'shortCode' => 'VasPro',
       'recipient' => strval(auth()->user()->phone),
       'enqueue' => 0,
       'message' => 'Dear, '.auth()->user()->name.', you have claimed ownership of an address at  '.$address->building_name.', Floor no. '.$address->floor_no.' Door no. '.$address->door_no,
       "callbackURL" => "http://vaspro.co.ke/dlr"
     ];
     
     
     $headers = array(
         'Cache-control: no-cache',
     );
     $url = "https://api.vaspro.co.ke/v3/BulkSMS/api/create";
     curl_setopt($ch,CURLOPT_URL, $url);
     curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
     curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);;
     curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,300);
     curl_setopt($ch,CURLOPT_TIMEOUT, 20);
     $response1 = curl_exec($ch);
     curl_close ($ch);
        return response()->json(['success' => 'Address Claimed succesfuly'],200);
    }
    public function action(Request $request){
        $action=$request->action;
        $address_id=$request->address_id;

        $address = Address::where('address_id',$address_id)->first();
        if($action == 'like'){
            $address->likes=$address->likes+1;
        }
        if($action == 'unlike'){
            if($address->likes == 0){
                $address->likes=0;
            }
            else{
            $address->likes=$address->likes-1;
            }
            
        }
        $address->save();
        return response()->json(['success' => 'liked'],200);
    }

    public function my_addresses(){
        $b_addresses=Address::where('owner_id',auth()->user()->id)->get();
        $r_addresses=ResidentialAddress::where('owner_id',auth()->user()->id)->get();
        if(! $b_addresses && ! $r_addresses){
            return response()->json(['error' => 'You have not claimed any address'],404);
        }
        return response()->json(['addresses' => ['business' => $b_addresses, 'residential' => $r_addresses]],200);
    }
}
