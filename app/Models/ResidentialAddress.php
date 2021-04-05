<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentialAddress extends Model
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
        $owner=User::find($value)->name;
        return $owner;
    }
    public function getCreatedByAttribute($value){
        $creator=User::find($value)->name;
        return $creator;
    }
}
