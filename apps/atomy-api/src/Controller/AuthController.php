<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;

final class AuthController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(): void
    {
        // The actual authentication is handled by App\Security\LoginAuthenticator
        // This method exists so the route is available for the firewall to intercept.
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
        // Handled by Symfony security logout listener
    }
}
