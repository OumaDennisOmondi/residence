<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ResidentialAddress;
use App\Notifications\AddressRegistered;
use App\Http\Controllers\SMSController;
class ResidentialAddressController extends Controller
{
    //
    protected $SMSController;
    public function __construct(SMSController $SMSController){
       $this->SMSController = $SMSController;
       $this->middleware('auth');
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' =>'required|numeric',
            'email' => 'required|email',
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
        
        $r_address = new ResidentialAddress;
        $r_address->phone = $request->phone;
        $r_address->email = $request->email;
        $r_address->road = $request->road;
        $r_address->county_id = $request->county_id;
        $r_address->subcounty_id = $request->subcounty_id;
        $r_address->landmark = $request->landmark;
        $r_address->building_name = $request->building_name;
        $r_address->floor_no = $request->floor_no;
        $r_address->door_no = $request->door_no;
        $r_address->image_path = $image_path;
        $r_address->pin_location = $request->pin_location;
        $r_address->location_id = $this->generateLocationID($request->building_name);
        $r_address->address_id =$this->generateAddressID();
        $r_address->created_by = auth()->user()->id;

        $r_address->save();

        //send sms with Location ID and Address ID
        $message = 'Thanks for registering, The Residential Location ID is '.$r_address->location_id;
        $this->SMSController->sendSMS($message, strval(auth()->user()->phone));
        
        auth()->user()->notify(new AddressRegistered($r_address));
        return response()->json(['addres_type' =>'residential','address'=> $r_address], 200);
    }

    //edit address
    public function edit(Request $request,$address_id){
        $validator = Validator::make($request->all(), [
            'phone' =>'required|numeric',
            'email' => 'required|email',
            'road' => 'required',
            'county_id' => 'required|numeric',
            'subcounty_id' =>'required|numeric',
            'landmark' => 'required|string|max:255',
            'floor_no' => 'required|string',
            'door_no' =>'required|string|max:255',
           
          ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if($request->hasFile('image_path')){ 
            $fileName = time().'.'.$request->image_path->extension();  
        
            $request->image_path->move(public_path('location_photos'), $fileName);
            $image_path=config('app.url').'/location_photos/'.$fileName;
        }
        $r_address = ResidentialAddress::where('address_id',$address_id)->first();;
        $r_address->phone = $request->phone;
        $r_address->email = $request->email;
        $r_address->road = $request->road;
        $r_address->county_id = $request->county_id;
        $r_address->subcounty_id = $request->subcounty_id;
        $r_address->landmark = $request->landmark;
        $r_address->floor_no = $request->floor_no;
        $r_address->door_no = $request->door_no;
        if($request->hasFile('image_path')){ 
            $r_address->image_path = $image_path;
        }
        $r_address->pin_location = $request->pin_location? $request->pin_location : $r_address->pin_location;
           

        $r_address->save();

        //send sms with Location ID and Address ID
        $message = 'You edited your Residential Address at Location ID '.$r_address->location_id;
        $this->SMSController->sendSMS($message, strval(auth()->user()->phone));
        
        //auth()->user()->notify(new AddressRegistered($r_address));
        return response()->json(['addres_type' =>'residential','address'=> $r_address], 200);
    }

    public function generateLocationID($building_name){
        $location=ResidentialAddress::where('building_name',$building_name)->first();
        if($location){
            $location_id=$location->location_id;
            return $location_id;
        }
        if(! $location && ResidentialAddress::all()->count() < 1){
            $location_id = 'R001';
            return $location_id;
        }
        $last_location_id = ResidentialAddress::all();
        $ids=[];
        foreach($last_location_id as $location){
            $location_id=$location->location_id;
            $id=explode('R',$location_id)[1];
            $id=(int)$id;
            array_push($ids,$id);

        }
        $last_location_id = max($ids);
        $new_location_id = (int)$last_location_id+1;
        $location_id = str_pad($new_location_id, 3, '0', STR_PAD_LEFT);
        return 'R'.$location_id;
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
