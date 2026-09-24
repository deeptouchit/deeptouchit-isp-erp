<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtpAccount extends Model
{
    protected $fillable = [
        'subscription_id', 'username', 'password', 'path', 'permissions', 'status'
    ];
    
    protected $hidden = ['password'];
    
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
