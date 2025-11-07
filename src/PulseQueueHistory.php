<?php

namespace Biigle\PulseQueueSizeCard;

use Illuminate\Database\Eloquent\Model;
use Biigle\PulseQueueSizeCard\Database\factories\PulseQueueHistoryFactory;

class PulseQueueHistory extends Model
{
    /**
     * Don't maintain timestamps for this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'queue',
        'values',
        'timestamp'
    ];

    protected $casts = [
        'values' => 'array', // cast JSON string to PHP array
    ];

    protected static function factory()
    {
        return PulseQueueHistoryFactory::new();
    }
}
