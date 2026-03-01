<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JWTAuthenticator extends AbstractAuthenticator
{
    private Configuration $config;

    public function __construct(
        private readonly UserRepository $userRepository,
        string $jwtSecret = 'a-very-secret-key-that-should-be-in-env'
    ) {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($jwtSecret)
        );
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $tokenString = str_replace('Bearer ', '', $authorizationHeader);

        try {
            $token = $this->config->parser()->parse($tokenString);

            if (!$this->config->validator()->validate($token, new SignedWith($this->config->signer(), $this->config->signingKey()))) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }

            if ($token->isExpired(new \DateTimeImmutable())) {
                throw new CustomUserMessageAuthenticationException('JWT token expired');
            }

            $userId = $token->claims()->get('uid');
            if (!$userId) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT payload');
            }

            return new SelfValidatingPassport(new UserBadge($userId, function($userId) {
                return $this->userRepository->find($userId);
            }));

        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => strtr($exception->getMessageKey(), $exception->getMessageData())], Response::HTTP_UNAUTHORIZED);
    }
}
