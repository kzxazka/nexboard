<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sketch extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'canvas_data',
        'thumbnail_path',
    ];

    protected $casts = [
        'canvas_data' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
