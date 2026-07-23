<?php

/**
 * Authentification compatible WordPress Application Passwords
 *
 * Supporte :
 *   - HTTP Basic Auth : Authorization: Basic base64(login:app_password)
 *     Le mot de passe peut être avec ou sans espaces (xxxx xxxx xxxx xxxx xxxx xxxx)
 *   - Bearer token (rétrocompatibilité avec l'ancien api_key)
 */
class Auth
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Vérifie les credentials de la requête en cours.
     * Retourne true si authentifié, false sinon.
     */
    public function check(): bool
    {
        $header = $this->authorizationHeader();
        if ($header === '') return false;

        if (preg_match('/^Basic\s+(.+)$/i', $header, $m)) {
            return $this->checkBasic($m[1]);
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return $this->checkBearer(trim($m[1]));
        }

        return false;
    }

    /**
     * Retourne true si une authentification est configurée (users ou api_key).
     * Si false, toutes les opérations d'écriture sont accessibles sans auth.
     */
    public function isRequired(): bool
    {
        $hasUsers = !empty($this->config['users']);
        $hasKey   = !empty($this->config['api_key']);
        return $hasUsers || $hasKey;
    }

    // -------------------------------------------------------------------------

    private function checkBasic(string $encoded): bool
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) return false;

        // Le login ne peut pas contenir ":", le mot de passe si
        $colonPos = strpos($decoded, ':');
        if ($colonPos === false) return false;

        $username = substr($decoded, 0, $colonPos);
        $password = substr($decoded, $colonPos + 1);

        // WordPress supprime les espaces avant de vérifier
        $password = str_replace(' ', '', $password);

        if ($password === '') return false;

        $users = $this->config['users'] ?? [];
        if (!isset($users[$username])) return false;

        foreach ($users[$username]['app_passwords'] ?? [] as $appPwd) {
            if (!empty($appPwd['hash']) && password_verify($password, $appPwd['hash'])) {
                return true;
            }
        }

        return false;
    }

    private function checkBearer(string $token): bool
    {
        $apiKey = $this->config['api_key'] ?? null;
        return $apiKey !== null && hash_equals($apiKey, $token);
    }

    private function authorizationHeader(): string
    {
        // Apache expose le header directement
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        // Apache avec certaines configs de mod_rewrite
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        // Nginx / PHP-FPM peut nécessiter une variable custom
        if (!empty($_SERVER['HTTP_X_AUTHORIZATION'])) {
            return $_SERVER['HTTP_X_AUTHORIZATION'];
        }
        return '';
    }
}
