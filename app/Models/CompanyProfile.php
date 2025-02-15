<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'businessname',
        'image',
        'businesstype',
        'email',
        'phonenumber',
        'gst/vat',
        'dateformat',
        'city',
        'address',
        'currency'
    ];
}
