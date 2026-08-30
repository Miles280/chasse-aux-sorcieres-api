<?php
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class DiscordOAuthAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private HttpClientInterface $client,
        private UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/auth/discord/callback');
    }

    public function authenticate(Request $request): Passport
    {
        $code = $request->query->get('code');
        if (!$code) {
            throw new \Exception('Code OAuth Discord manquant');
        }

        // 1️⃣ Échanger le code contre un access_token
        $tokenResponse = $this->client->request('POST', 'https://discord.com/api/oauth2/token', [
            'body' => [
                'client_id' => $_ENV['DISCORD_CLIENT_ID'],
                'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'],
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'],
            ],
        ])->toArray();

        $accessToken = $tokenResponse['access_token'];

        // 2️⃣ Récupérer les infos utilisateur Discord
        $userResponse = $this->client->request('GET', 'https://discord.com/api/users/@me', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
            ],
        ])->toArray();

        $discordId = $userResponse['id'];
        $user = $this->userRepository->findOneBy(['discordId' => $discordId]);

        if (!$user) {
            $user = new User();
            $user->setDiscordId($discordId);
            $user->setDiscordUsername($userResponse['username']);
            $user->setDiscordGlobalName($userResponse['global_name'] ?? null);
            $user->setDiscordAvatar($userResponse['avatar'] ?? null);
        }

        $user->setDiscordAccessToken($accessToken);
        $user->setLastLoginAt(new \DateTime());
        $this->userRepository->save($user, true);

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn() => $user));
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // Tu pourrais ici rediriger vers ton front (Angular) avec un JWT custom de ton API
        return new JsonResponse(['message' => 'Connexion Discord réussie !']);
    }

    public function onAuthenticationFailure(Request $request, $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], 401);
    }
}
