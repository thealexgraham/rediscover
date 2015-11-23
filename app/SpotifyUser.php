<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpotifyUser extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['spotify_id', 'display_name'];
}
