<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class DiscordAuthenticator extends AbstractAuthenticator
{
    const DISCORD_AUTH_KEY = 'discord-auth';

    public function __construct(
        private readonly UserRepository  $userRepo,
        private readonly RouterInterface $router
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oauth_discord_auth' && $this->isValidRequest($request);
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $request->query->get('accessToken');

        if (!$this->isValidRequest($request)) {
            throw new AuthenticationException('Invalid request');
        }

        if (null === $accessToken) {
            throw new AuthenticationException('No access token provided');
        }

        $user = $this->userRepo->findOneBy(['accessToken' => $accessToken]);

        if (!$user) {
            throw new AuthenticationException('Wrong access token');
        }

        $userBadge = new UserBadge($user->getUserIdentifier(), function () use ($user) {
            return $user;
        });

        $request->getSession()->remove(self::DISCORD_AUTH_KEY);

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var Session $session */
        $session = $request->getSession();

        $session->remove(self::DISCORD_AUTH_KEY);
        $session->getFlashBag()->add('danger', $exception->getMessage());

        return new RedirectResponse($this->router->generate('app_home'));
    }

    private function isValidRequest(Request $request): bool
    {
        return true === $request->getSession()->get(self::DISCORD_AUTH_KEY);
    }
}