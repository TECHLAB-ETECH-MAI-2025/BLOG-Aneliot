<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Security;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';    

    public function __construct(
    private UrlGeneratorInterface $urlGenerator,
    private string $mercureJwtSecret
) {}


    public function authenticate(Request $request): Passport
    {
        $email = $request->get('email', '');

        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        $topic = "http://localhost:8000/conversation/*";

        $payload = [
            'mercure' => ['subscribe' => [$topic]],
            'exp' => time() + 3600,
        ];

        $jwt = JWT::encode($payload,  $this->mercureJwtSecret, 'HS256');

        $cookie = Cookie::create('mercureAuthorization')
            ->withValue($jwt)
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withPath('/.well-known/mercure')
            ->withSameSite('Lax');

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $response = new RedirectResponse($targetPath);
        } else {
            $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        // Ajouter le cookie Mercure à la réponse
        $response->headers->setCookie($cookie);

        return $response;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}