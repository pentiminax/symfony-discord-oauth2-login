<?php

namespace App\Service;

use App\Model\DiscordUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordApiService
{
    const AUTHORIZATION_BASE_URI = 'https://discord.com/api/oauth2/authorize';

    const USERS_ME_ENDPOINT = '/api/users/@me';

    public function __construct(
        private readonly HttpClientInterface $discordApiClient,
        private readonly SerializerInterface $serializer,
        private readonly string              $clientId,
        private readonly string              $redirectUri
    )
    {
    }

    public function getAuthorizationUrl(array $scope): string
    {
        return self::AUTHORIZATION_BASE_URI . "?" . http_build_query([
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'response_type' => 'token',
                'scope' => implode(' ', $scope),
                'prompt' => 'none'
            ]);
    }

    public function fetchUser(string $accessToken): DiscordUser
    {
        $response = $this->discordApiClient->request(Request::METHOD_GET, self::USERS_ME_ENDPOINT, [
            'auth_bearer' => $accessToken
        ]);

        $data = $response->getContent();

        return $this->serializer->deserialize($data, DiscordUser::class, 'json');
    }
}