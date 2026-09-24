<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'message', 'is_staff'
    ];
    
    protected $casts = [
        'is_staff' => 'boolean'
    ];

    protected $appends = [
        'formatted_created_at'
    ];

    public function getFormattedCreatedAtAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->format('M d, Y h:i A') : null;
    }
    
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
