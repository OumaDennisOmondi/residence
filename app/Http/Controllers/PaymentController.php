<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Address;
use App\Models\User;
use App\Models\ResidentialAddress;
use App\Notifications\AddressClaimed;
class PaymentController extends Controller
{
    //

    public function processClaim(Request $request){
        if($request->Body['stkCallback']['ResultCode'] == '0'){
         $phone= $request->Body['stkCallback']['CallbackMetadata']['Item'][4]['Value'];
         $amount= $request->Body['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
         $paid_at= $request->Body['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
         $transaction_ref= $request->Body['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
         $payment = Payment::where('phone',$phone)->where('amount',0)->latest()->first();
        
         //update payment
         $payment->amount=$amount;
         $payment->paid_at= $paid_at;
         $payment->transaction_ref= $transaction_ref;
         $payment->save();

         //claim address
            $address=Address::where('address_id',$payment->address_id)->first();
            if(!$address){
                $address=ResidentialAddress::where('address_id',$payment->address_id)->first();
            }
        //get user
        $user = User::find($payment->user_id);
        $address->owner_id=$user->id;
        $address->claimed=true;
        $address->save();
        //notify
        $user->notify(new AddressClaimed($address));
        //send sms
        $ch = curl_init();
        $params=[
       'apiKey' => '120ca9639da262edc34804590b59cb40',
       'shortCode' => 'VasPro',
       'recipient' => strval($user->phone),
       'enqueue' => 0,
       'message' => 'Dear, '.$user->name.', you have claimed ownership of an address at  '.$address->building_name.', Floor no. '.$address->floor_no.' Door no. '.$address->door_no,
       "callbackURL" => "http://vaspro.co.ke/dlr"
     ];
     
     
     $headers = array(
         'Cache-control: no-cache',
     );
     $url = "https://api.vaspro.co.ke/v3/BulkSMS/api/create";
     curl_setopt($ch,CURLOPT_URL, $url);
     curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
     curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
     curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,300);
     curl_setopt($ch,CURLOPT_TIMEOUT, 20);
     $response1 = curl_exec($ch);
     curl_close ($ch);
     
        }
    }
}
