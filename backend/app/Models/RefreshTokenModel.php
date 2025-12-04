<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\RefreshToken;

class RefreshTokenModel extends Model
{
    protected $table = 'refresh_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = RefreshToken::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'token_hash',
        'expires_at',
        'is_revoked',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Create a new refresh token
     */
    public function createToken(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

        $this->insert([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'is_revoked' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'token' => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Validate and get refresh token
     */
    public function validateToken(string $token): ?RefreshToken
    {
        $tokenHash = hash('sha256', $token);

        return $this->where('token_hash', $tokenHash)
                    ->where('is_revoked', false)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /**
     * Revoke a token
     */
    public function revokeToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);

        return $this->where('token_hash', $tokenHash)
                    ->set(['is_revoked' => true])
                    ->update();
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->set(['is_revoked' => true])
                    ->update();
    }
}
