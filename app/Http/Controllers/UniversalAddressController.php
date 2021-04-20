<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\ResidentialAddress;
use App\Notifications\AddressClaimed;
use App\Notifications\AddressUnclaimed;
use App\Models\Payment;
use App\Http\Controllers\SMSController;
class UniversalAddressController extends Controller
{
    //
    protected $SMSController;
    public function __construct(SMSController $SMSController){
        $this->SMSController = $SMSController;
        $this->middleware('auth', ['except' => ['home','pay','view','action','send_sms']]);
    }
    public function home(){
        //list business addresess that have been claimed
        $addresses = Address::where('claimed', 1)->paginate(15);
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

        //save payment details
        $phonenumber = substr($request->phone, -9, strlen($request->phone));
        $payee_phone=sprintf('254%s', trim($phonenumber));
        Payment::create([
            'user_id' => auth()->user()->id,
            'phone'=> $payee_phone,
            'address_id' => $address_id
        ]);
        //return response()->json(['success' => 'Payment saved'],200);

        //initiate mpesa
        $this->initiateMpesa(3,$request->phone);

    }
    public function unclaim_address(Request $request){
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
        
        $address->owner_id=null;
        $address->claimed=false;
        $address->save();

        //send sms
     $message = 'Dear, '.auth()->user()->name.', you have Unclaimed ownership of an address at  '.$address->building_name.', Floor no. '.$address->floor_no.' Door no. '.$address->door_no;
     $this->SMSController->sendSMS($message, strval(auth()->user()->phone));
    
        auth()->user()->notify(new AddressUnclaimed($address));
        return response()->json(['success' => 'Address Unclaimed succesfuly'],200);
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
    public function my_unclaimed_addresses(){
        $b_addresses=Address::where('created_by',auth()->user()->id)->get();
        $r_addresses=ResidentialAddress::where('created_by',auth()->user()->id)->get();
        if(! $b_addresses && ! $r_addresses){
            return response()->json(['error' => 'You have not registered any address'],404);
        }
        return response()->json(['addresses' => ['business' => $b_addresses, 'residential' => $r_addresses]],200);
    }

    public function all_my_addresses(){
        $b_addresses=Address::where('created_by',auth()->user()->id)->get();
        $r_addresses=ResidentialAddress::where('created_by',auth()->user()->id)->get();
        if(! $b_addresses && ! $r_addresses){
            return response()->json(['error' => 'You have not registered any address'],404);
        }
        return response()->json(['addresses' => ['business' => $b_addresses, 'residential' => $r_addresses]],200);
    }

    //initiate payment
    public function initiateMpesa($amount_to_pay,$payee_phone){
        function lipaNaMpesaPassword()
{
    //timestamp
    $timestamp = date('YmdHis');
    //passkey
    $passKey ="7a8b3953d3d6acebf64fc6c73483fb1071ac43f7954846679cab3b8b2a916de9";
    $businessShortCOde ='7299801';
    //generate password
    $mpesaPassword = base64_encode($businessShortCOde.$passKey.$timestamp);

    return $mpesaPassword;
}
    

   function newAccessToken()
   {
       $consumer_key="ORGP3EYUECfBSaWaGGsVnpoluhzSbbuL";
       $consumer_secret="IUj5tqmjxypO2RA4";
       $credentials = base64_encode($consumer_key.":".$consumer_secret);
       $url = "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";


       $curl = curl_init();
       curl_setopt($curl, CURLOPT_URL, $url);
       curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$credentials,"Content-Type:application/json"));
       curl_setopt($curl, CURLOPT_HEADER, false);
       curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
       $curl_response = curl_exec($curl);
       $access_token=json_decode($curl_response);
       curl_close($curl);
       
       return $access_token->access_token;
   }



   function stkPush($amount,$phone)
   {
       //    $user = $request->user;
       //    $amount = $request->amount;
       //    $phone =  $request->phone;
       //    $formatedPhone = substr($phone, 1);//726582228
       //    $code = "254";
       //    $phoneNumber = $code.$formatedPhone;//254726582228

      
       


       $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
       $curl_post_data = [
            'BusinessShortCode' =>'7299801',
            'Password' => lipaNaMpesaPassword(),
            'Timestamp' => date('YmdHis'),
            'TransactionType' => 'CustomerBuyGoodsOnline',
            'Amount' => $amount,
            'PartyA' => strval($phone),
            'PartyB' => 5297253,
            'PhoneNumber' => strval($phone),
            'CallBackURL' => 'https://residenceafrica.co.ke/area51/callback.php',
            'AccountReference' => "Residence",
            'TransactionDesc' => "lipa Na M-PESA"
        ];


       $data_string = json_encode($curl_post_data);


       $curl = curl_init();
       curl_setopt($curl, CURLOPT_URL, $url);
       curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.newAccessToken()));
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($curl, CURLOPT_POST, true);
       curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
       $curl_response = curl_exec($curl);
       $curl_response=json_decode($curl_response,true);
       if($curl_response['ResponseCode'] == '0'){
        print_r(json_encode(['success' => 'Payment initiated']));
        exit();
       }
       print_r(json_encode(['error' => 'We cant initiate your payment']));
   }
   stkPush($amount_to_pay,$payee_phone);
    }
    //tset mpesa
    public function pay(){
        $this->initiateMpesa(2);
    }
    //test sms
    public function send_sms(){
        $this->SMSController->sendSMS('Hello world!','254742321640');
    }
}
