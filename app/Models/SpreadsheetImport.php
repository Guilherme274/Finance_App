<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpreadsheetImport extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'type',
        'rows_imported',
        'rows_skipped',
        'status',
        'column_mapping',
        'notes',
    ];

    protected $casts = [
        'column_mapping' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
