<?php

namespace App\Models\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    protected function casts(): array
    {
        return [
            'skip_authorization' => 'boolean',
            'show_on_dashboard' => 'boolean',
        ];
    }

    /**
     * Trusted first-party apps skip the consent screen for a seamless SSO experience.
     *
     * @param  \Laravel\Passport\Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty() && $this->skip_authorization;
    }

    /**
     * Base URL of the app (explicit homepage, or derived from the first redirect URI).
     */
    public function appUrl(): ?string
    {
        if ($this->homepage_url) {
            return $this->homepage_url;
        }

        $uri = $this->redirect_uris[0] ?? null;

        if (! $uri || ! ($parts = parse_url($uri)) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * Whether the given URL belongs to one of this client's registered origins.
     */
    public function ownsUrl(string $url): bool
    {
        $origin = static fn (?string $u) => ($p = parse_url((string) $u)) && ! empty($p['host'])
            ? strtolower(($p['scheme'] ?? 'http').'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : ''))
            : null;

        $target = $origin($url);

        if (! $target) {
            return false;
        }

        $known = array_filter(array_map($origin, [...$this->redirect_uris, $this->homepage_url]));

        return in_array($target, $known, true);
    }
}
