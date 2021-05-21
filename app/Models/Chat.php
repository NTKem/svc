<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $table = 'chat';
    protected $primaryKey = 'id';
    protected $fillable = [
        'chat_uuid',
        'uuid_user',
        'uuid_user_to',
        'message',
        'seen',
    ];
}
