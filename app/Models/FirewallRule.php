<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirewallRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'port',
        'protocol',
        'action',
        'direction',
        'from_ip',
        'to_ip',
        'is_system',
        'status',
        'ufw_rule_number',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'ufw_rule_number' => 'integer',
    ];
}
