<?php

namespace BrendanMacKenzie\IntegrationManager\Flows;

interface AuthenticationInterface
{
    public function authenticate();

    public function getAccessToken();

    public function getAuthenticationHeaders();
}