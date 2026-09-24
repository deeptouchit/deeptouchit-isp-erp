<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
{
    protected $table = 'databases';
    
    protected $fillable = [
        'subscription_id', 'name', 'db_user', 'db_password',
        'host', 'port', 'charset', 'collation', 'size_bytes', 'status'
    ];
    
    protected $hidden = ['db_password'];
    
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
