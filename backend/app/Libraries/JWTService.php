<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
    protected string $secretKey;
    protected string $algorithm = 'HS256';
    protected int $accessTokenTTL = 3600; // 1 hour
    protected int $refreshTokenTTL = 2592000; // 30 days

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET_KEY', 'your-secret-key-change-in-production');
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(array $userData): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenTTL;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => env('app.baseURL', 'crm-questionnaire'),
            'sub' => $userData['id'],
            'data' => [
                'userId' => $userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'organizationId' => $userData['organization_id'],
                'departmentId' => $userData['department_id'],
            ],
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate and decode access token
     */
    public function validateAccessToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            log_message('debug', 'JWT validation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get token expiration time in seconds
     */
    public function getAccessTokenTTL(): int
    {
        return $this->accessTokenTTL;
    }

    /**
     * Get refresh token expiration time in seconds
     */
    public function getRefreshTokenTTL(): int
    {
        return $this->refreshTokenTTL;
    }

    /**
     * Extract user data from decoded token
     */
    public function getUserFromToken(object $decoded): array
    {
        return (array) $decoded->data;
    }

    /**
     * Check if token is about to expire (within 5 minutes)
     */
    public function isTokenExpiringSoon(object $decoded): bool
    {
        return ($decoded->exp - time()) < 300;
    }
}
