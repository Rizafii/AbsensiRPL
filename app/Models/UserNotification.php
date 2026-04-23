<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

class UserNotification extends LaravelDatabaseNotification
{
    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = ['id'];
}
