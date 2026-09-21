<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\ApiToken;
use App\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiTokenRepositoryInterface $tokens,
        private ClockInterface $clock,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $token = $this->tokens->findByHash(ApiToken::hash($accessToken));

        if ($token === null || $token->isExpiredAt($this->clock->now())) {
            throw new BadCredentialsException('Invalid or expired API token.');
        }

        return new UserBadge($token->user()->getUserIdentifier());
    }
}
