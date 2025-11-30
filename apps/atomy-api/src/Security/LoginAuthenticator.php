<?php

declare(strict_types=1);

namespace App\Security;

use Nexus\Domain\Identity\Contracts\PasswordHasherInterface as IdentityPasswordHasherInterface;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface as NexusUserRepositoryInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

final class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly NexusUserRepositoryInterface $users,
        private readonly IdentityPasswordHasherInterface $hasher,
        private readonly ?RateLimiterFactory $loginLimiter = null
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/login' && $request->getMethod() === 'POST';
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $email = $data['email'] ?? $request->request->get('email');
        $password = $data['password'] ?? $request->request->get('password');

        if (!$email || !$password) {
            throw new AuthenticationException('Missing credentials');
        }

        // rate limit check (if configured)
        if ($this->loginLimiter !== null) {
            $key = sprintf('%s-%s', (string) ($email ?? 'unknown'), (string) $request->getClientIp());
            $limiter = $this->loginLimiter->create($key);
            $limit = $limiter->consume();
            if (!$limit->isAccepted()) {
                throw new AuthenticationException('Too many login attempts, try again later.');
            }
        }

        return new Passport(
            new UserBadge($email, fn($userIdentifier) => $this->users->findByEmailOrNull($userIdentifier)),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        // reset failed attempts on successful login
        if (method_exists($user, 'getId') && method_exists($this->users, 'resetFailedLoginAttempts')) {
            $this->users->resetFailedLoginAttempts($user->getId());
            $this->users->updateLastLogin($user->getId());
        }

        // If password needs rehash, rehash and persist (be careful retrieving plain password)
        $payload = json_decode($request->getContent() ?: '{}', true);
        $plain = $payload['password'] ?? $request->request->get('password');

        if ($plain && method_exists($user, 'getPasswordHash')) {
            $hash = $user->getPasswordHash();
            if ($this->hasher->needsRehash($hash)) {
                if (method_exists($this->users, 'update')) {
                    $newHash = $this->hasher->hash($plain);
                    $this->users->update($user->getId(), ['password_hash' => $newHash]);
                }
            }
        }

        return new JsonResponse(['ok' => true, 'user' => $user->getId() ?? null]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Increment failed attempts and possibly lock account
        $data = json_decode($request->getContent() ?: '{}', true);
        $email = $data['email'] ?? $request->request->get('email');

        if ($email) {
            $user = $this->users->findByEmailOrNull($email);
            if ($user !== null) {
                $count = $this->users->incrementFailedLoginAttempts($user->getId());
                // lock threshold - 5 attempts
                if ($count >= 5 && method_exists($this->users, 'lockAccount')) {
                    $this->users->lockAccount($user->getId(), 'too many failed logins');
                }
            }
        }

        return new JsonResponse(['error' => 'Authentication Failed', 'message' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Authentication Required'], Response::HTTP_UNAUTHORIZED);
    }
}
