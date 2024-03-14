<?php

namespace BrendanMacKenzie\IntegrationManager\Models;

use BrendanMacKenzie\IntegrationManager\Utils\Encryptable;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $table = 'integrations';
    
    protected $fillable = [
        'owner_type',
        'owner_id',
        'integration_option_id',
        'credentials',        
    ];

    protected $hidden = [
        'credentials',
    ];

    public function owner()
    {
        return $this->morphTo('owner');
    }

    public function options()
    {
        return $this->belongsTo(IntegrationOption::class);
    }
}