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
    protected $SMSController;
    public function __construct(SMSController $SMSController){
        $this->SMSController = $SMSController;
    }
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
        $message = 'Dear, '.$user->name.', you have claimed ownership of an address at  '.$address->building_name.', Floor no. '.$address->floor_no.' Door no. '.$address->door_no;
        $this->SMSController->sendSMS($message, strval($user->phone));
        
        }
    }
}
