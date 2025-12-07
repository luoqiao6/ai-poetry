<?php

namespace PoetryAgent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 推荐记录模型
 */
class Recommendation extends Model
{
    protected $table = 'recommendations';
    
    public $timestamps = true;
    
    protected $fillable = [
        'user_id',
        'positive_prompt',
        'negative_prompt',
        'image_path',
        'image_description',
        'context',
        'poem_title',
        'poem_content',
        'author',
        'dynasty',
        'appreciation',
        'model_name',
        'model_version',
        'status',
        'error_message',
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

