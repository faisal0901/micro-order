<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table="orders";
    protected $fillable=["status","user_id","course_id","metadata","snap_url"];
    protected $cast=["created_at"=>"datetime:Y-m-d H:m:s","updated_at"=>"datetime:Y-m-d H:m:s","metadata"=>"array"];
    
}
