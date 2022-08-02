<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\DiscordApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiscordController extends AbstractController
{
    public function __construct(
        private readonly DiscordApiService $discordApiService
    )
    {
    }

    #[Route('/discord/connect', name: 'oauth_discord')]
    public function connect(): Response
    {
        $scope = ['identify', 'email'];

        return $this->redirect($this->discordApiService->getAuthorizationUrl($scope));
    }

    #[Route('/discord/auth', name: 'oauth_discord_auth')]
    public function auth(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/discord/check', name: 'oauth_discord_check')]
    public function check(EntityManagerInterface $em, Request $request, UserRepository $userRepo): Response
    {
        $accessToken = $request->get('access_token');

        if (!$accessToken) {
            return $this->render('discord/check.html.twig');
        }

        $discordUser = $this->discordApiService->fetchUser($accessToken);

        $user = $userRepo->findOneBy(['discordId' => $discordUser->id]);

        if ($user) {
            return $this->redirectToRoute('oauth_discord_auth', [
                'userId' => $user->getId()
            ]);
        }

        $user = new User();

        $user->setUsername($discordUser->username);
        $user->setEmail($discordUser->email);
        $user->setAvatar($discordUser->avatar);
        $user->setDiscordId($discordUser->id);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('oauth_discord_auth');
    }
}
