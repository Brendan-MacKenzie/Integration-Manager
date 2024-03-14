<?php

namespace BrendanMacKenzie\IntegrationManager\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOption extends Model
{
    protected $table = 'integration_options';
    
    protected $fillable = [
        'name',    
    ];

    public function integrations()
    {
        return $this->belongsTo(Integration::class);
    }
}