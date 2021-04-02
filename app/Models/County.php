<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use App\Models\Subcounty;
class County extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];

    public function subcounties(){
       // return $this->hasMany(Subcounty::class);
    }
}
