<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Address;
use App\Notifications\AddressRegistered;
class AddressController extends Controller
{
    //
   
    public function __construct(){
        $this->middleware('auth');
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|unique:addresses|max:255',
            'service_id' => 'required|numeric',
            'service_description' => 'required|string|max:255',
            'phone' =>'required|numeric|unique:addresses',
            'email' => 'required|email|unique:addresses',
            'road' => 'required',
            'county_id' => 'required|numeric',
            'subcounty_id' =>'required|numeric',
            'landmark' => 'required|string|max:255',
            'building_name' => 'required|string',
            'floor_no' => 'required|string',
            'door_no' =>'required|string|max:255',
            'image_path' => 'required|mimes:jpeg,jpg,png|max:2048',
            'pin_location' => 'required|string',
           
          ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $fileName = time().'.'.$request->image_path->extension();  
   
        $request->image_path->move(public_path('location_photos'), $fileName);
        $image_path=config('app.url').'/location_photos/'.$fileName;
        
        $b_address = new Address;
        $b_address->business_name = $request->business_name;
        $b_address->service_id = $request->service_id;
        $b_address->service_description = $request->service_description;
        $b_address->phone = $request->phone;
        $b_address->email = $request->email;
        $b_address->road = $request->road;
        $b_address->county_id = $request->county_id;
        $b_address->subcounty_id = $request->subcounty_id;
        $b_address->landmark = $request->landmark;
        $b_address->building_name = $request->building_name;
        $b_address->floor_no = $request->floor_no;
        $b_address->door_no = $request->door_no;
        $b_address->image_path = $image_path;
        $b_address->pin_location = $request->pin_location;
        $b_address->location_id = $this->generateLocationID($request->building_name);
        $b_address->address_id =$this->generateAddressID();
        $b_address->created_by = auth()->user()->id;

        $b_address->save();

        //send sms with Location ID and Address ID
        $ch = curl_init();
        $params=[
       'apiKey' => '120ca9639da262edc34804590b59cb40',
       'shortCode' => 'VasPro',
       'recipient' => strval(auth()->user()->phone),
       'enqueue' => 0,
       'message' => 'Thanks for registering '.$b_address->business_name.', The Residence Business Location ID is '.$b_address->location_id,
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
        auth()->user()->notify(new AddressRegistered($b_address));
        return response()->json(['addres_type' =>'business','address'=> $b_address], 200);
    }

    public function generateLocationID($building_name){
        $location=Address::where('building_name',$building_name)->first();
        if($location){
            $location_id=$location->location_id;
            return $location_id;
        }
        if(! $location && Address::all()->count() < 1){
            $location_id = 'B0001';
            return $location_id;
        }
        $last_location_id = Address::all();
        $ids=[];
        foreach($last_location_id as $location){
            $location_id=$location->location_id;
            $id=explode('B',$location_id)[1];
            $id=(int)$id;
            array_push($ids,$id);

        }
        $last_location_id = max($ids);
        $new_location_id = (int)$last_location_id+1;
        $location_id = str_pad($new_location_id, 4, '0', STR_PAD_LEFT);
        return 'B'.$location_id;
    }
    public function generateAddressID(){
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 9; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }
}
