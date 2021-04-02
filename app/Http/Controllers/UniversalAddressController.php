<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\ResidentialAddress;
class UniversalAddressController extends Controller
{
    //
    public function __construct(){
        //$this->middleware('auth');
    }

    public function search_to_claim(Request $request){
        $location_id=$request->query('location_id');
        if (str_contains($location_id, 'B')) {
            $addresses=Address::where('location_id',$location_id)->get();
            return response()->json(['address_type' => 'business', 'addresess'=> $addresses],200);
        }
        elseif(str_contains($location_id, 'R')){
            $addresses=ResidentialAddress::where('location_id',$location_id)->get();
            return response()->json(['address_type' => 'residential', 'addresess'=> $addresses],200);
        }
    }
}
