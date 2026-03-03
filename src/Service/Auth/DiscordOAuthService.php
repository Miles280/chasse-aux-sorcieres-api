<?php

namespace App\Service\Auth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, string $clientId, string $clientSecret, string $redirectUri
    ) {
        $this->httpClient = $httpClient;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    public function getAccessToken(string $code): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://discord.com/api/oauth2/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query([
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => trim($this->redirectUri),
                ]),
            ]);

            return $response->toArray();
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            throw new \Exception('Discord OAuth error: ' . $e->getMessage() . ' - ' . $e->getResponse()->getContent(false));
        }
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = $this->httpClient->request('GET', 'https://discord.com/api/users/@me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $response->toArray();
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = $this->httpClient->request('POST', 'https://discord.com/api/oauth2/token', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]),
        ]);

        return $response->toArray();
    }
}
