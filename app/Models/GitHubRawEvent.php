<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GitHubRawEvent extends Model
{
    /**
     * The name of the database table.
     * 
     * @var string
     */
    protected $table = "github_raw_events";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'event_name',
        'event_action',
        'payload',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted() {
        // listen for various methods so we can modify the structure of the
        // payload depending on whether we are manipulating or retrieving the
        // record
        static::saving(function($event) {
            if (is_array($event->payload)) {
                $event->payload = json_encode($event->payload);
            }
        });
        static::retrieved(function($event) {
            if (is_string($event->payload)) {
                $event->payload = json_decode($event->payload, true);
            }
        });
    }
}
