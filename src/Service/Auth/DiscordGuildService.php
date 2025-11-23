<?php

namespace App\Service\Auth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordGuildService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $guildId
    ) {}

    public function getUserGuildMember(string $accessToken): array
    {
        $response = $this->httpClient->request(
            'GET',
            "https://discord.com/api/users/@me/guilds/{$this->guildId}/member",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]
        );

        return $response->toArray();
    }
}
