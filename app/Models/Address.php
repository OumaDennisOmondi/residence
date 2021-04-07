<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\County;
use App\Models\User;
class Address extends Model
{
    use HasFactory;
    protected $casts = [
        'claimed' => 'boolean',
    ];

    public function getCountyIdAttribute($value){
        $county_id=County::find($value)->name;
        return $county_id;
    }
    public function getSubcountyIdAttribute($value){
        $subcounty_id=Subcounty::find($value)->name;
        return $subcounty_id;
    }
    public function getOwnerIdAttribute($value){
        if($owner=User::find($value)){
            return $owner->name;
        }
        return 'unclaimed';
    }
    public function getCreatedByAttribute($value){
        $creator=User::find($value)->name;
        return $creator;
    }
}
