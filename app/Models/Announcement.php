<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'announcement_id';
    const UPDATED_AT = null;

    protected $fillable = [
        'title', 'slug', 'content', 'cover_image', 'audience',
        'is_published', 'posted_by', 'posted_date', 'expiry_date',
    ];
}
