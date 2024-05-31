<?php

namespace BrendanMacKenzie\IntegrationManager\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $table = 'integrations';
    
    protected $fillable = [
        'owner_type',
        'owner_id',
        'integration_option_id',
        'credentials',
        'base_url',
        'auth_url',
        'authorization_url',
        'authentication_endpoint',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function owner()
    {
        return $this->morphTo('owner');
    }

    public function option()
    {
        return $this->belongsTo(IntegrationOption::class, 'integration_option_id');
    }

    public function setCredentials(array $credentials): void
    {
        $encrypted = $this->encrypt($credentials);
        $this->credentials = $encrypted;
        $this->save();
        $this->refresh();
    }

    public function addCredential(string $key, string $value)
    {
        $credentials = $this->decrypt();
        $credentials[$key] = $value;
        $this->setCredentials($credentials);
    }

    public function removeCredential(string $key)
    {
        $credentials = $this->decrypt();
        if (array_key_exists($key, $credentials)) {
            unset($credentials[$key]);
        }
        $this->setCredentials($credentials);
    }

    public function getCredential(string $key)
    {
        $credentials = $this->decrypt();
        if (array_key_exists($key, $credentials)) {
            return $credentials[$key];
        }

        return;
    }

    protected function encrypt(array $credentials) : array
    {
        $encrypted = [];
        foreach ($credentials as $key => $value) {
            $encrypted[$key] = encrypt($value);
        }

        return $encrypted;
    }

    protected function decrypt() : array
    {
        $credentials = json_decode($this->credentials, true);
        
        if (!$credentials) {
            return [];
        }

        $decrypted = [];
        foreach ($credentials as $key => $value) {
            $decrypted[$key] = decrypt($value);
        }

        return $decrypted;
    }
}