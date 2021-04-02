<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcounty;
class SubcountyController extends Controller
{
    //
    public function index(Request $request){
        $county_id=$request->county_id;
        $subcounties= Subcounty::where('county_id',$county_id)->get();
        return response()->json(['subcounties' => $subcounties], 200);
    }
}
