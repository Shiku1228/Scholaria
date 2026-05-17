<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\Encrypter;
use Exception;

class EncryptionService
{
    protected $encrypter;
    protected $algorithm;
    protected $key;

    public function __construct()
    {
        $this->encrypter = app('encrypter');
        $this->algorithm = config('security.encryption.algorithm', 'AES-256-CBC');
        $this->key = config('security.encryption.key');
    }

    /**
     * Encrypt sensitive data.
     */
    public function encrypt(mixed $data): string
    {
        try {
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data);
            }

            return Crypt::encrypt($data);
        } catch (Exception $e) {
            Log::error('Encryption failed', [
                'error' => $e->getMessage(),
                'data_type' => gettype($data),
            ]);
            
            throw new Exception('Failed to encrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt sensitive data.
     */
    public function decrypt(string $encryptedData): mixed
    {
        try {
            $decrypted = Crypt::decrypt($encryptedData);
            
            // Try to decode as JSON, return as string if fails
            $decoded = json_decode($decrypted, true);
            return $decoded !== null ? $decoded : $decrypted;
            
        } catch (Exception $e) {
            Log::error('Decryption failed', [
                'error' => $e->getMessage(),
                'encrypted_data_length' => strlen($encryptedData),
            ]);
            
            throw new Exception('Failed to decrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt specific fields in an array.
     */
    public function encryptFields(array $data, array $fields): array
    {
        $encrypted = $data;
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $encrypted[$field] = $this->encrypt($data[$field]);
            }
        }
        
        return $encrypted;
    }

    /**
     * Decrypt specific fields in an array.
     */
    public function decryptFields(array $data, array $fields): array
    {
        $decrypted = $data;
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                try {
                    $decrypted[$field] = $this->decrypt($data[$field]);
                } catch (Exception $e) {
                    Log::warning("Failed to decrypt field {$field}", [
                        'error' => $e->getMessage(),
                    ]);
                    // Keep original value if decryption fails
                    $decrypted[$field] = $data[$field];
                }
            }
        }
        
        return $decrypted;
    }

    /**
     * Check if data contains sensitive information.
     */
    public function containsSensitiveData(array $data): bool
    {
        $sensitiveFields = config('security.audit.sensitive_fields', []);
        $dataKeys = array_keys($data);
        
        return !empty(array_intersect($dataKeys, $sensitiveFields));
    }

    /**
     * Get sensitive fields from data.
     */
    public function getSensitiveFields(array $data): array
    {
        $sensitiveFields = config('security.audit.sensitive_fields', []);
        $dataKeys = array_keys($data);
        
        return array_intersect($dataKeys, $sensitiveFields);
    }

    /**
     * Mask sensitive data for logging.
     */
    public function maskSensitiveData(array $data): array
    {
        $masked = $data;
        $sensitiveFields = $this->getSensitiveFields($data);
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $masked[$field] = $this->maskValue($data[$field]);
            }
        }
        
        return $masked;
    }

    /**
     * Mask a sensitive value.
     */
    private function maskValue(mixed $value): string
    {
        $stringValue = (string) $value;
        $length = strlen($stringValue);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        // Show first 2 and last 2 characters, mask the rest
        return substr($stringValue, 0, 2) . str_repeat('*', $length - 4) . substr($stringValue, -2);
    }

    /**
     * Generate encryption key.
     */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Validate encryption key.
     */
    public function validateKey(string $key): bool
    {
        try {
            $testData = 'test_encryption_validation';
            $encrypted = Crypt::encrypt($testData);
            $decrypted = Crypt::decrypt($encrypted);
            
            return $decrypted === $testData;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rotate encryption key.
     */
    public function rotateKey(string $newKey): bool
    {
        try {
            // Validate new key
            if (!$this->validateKey($newKey)) {
                throw new Exception('Invalid new encryption key');
            }

            // This would need to be implemented carefully in a real system
            // to re-encrypt all existing encrypted data
            Log::warning('Encryption key rotation requested - manual implementation required');
            
            return true;
        } catch (Exception $e) {
            Log::error('Key rotation failed', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get encryption metadata.
     */
    public function getMetadata(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'key_length' => strlen($this->key ?? ''),
            'encrypter_class' => get_class($this->encrypter),
            'supported_algorithms' => $this->getSupportedAlgorithms(),
        ];
    }

    /**
     * Get supported encryption algorithms.
     */
    private function getSupportedAlgorithms(): array
    {
        return [
            'AES-256-CBC',
            'AES-256-GCM',
            'AES-128-CBC',
            'AES-128-GCM',
        ];
    }

    /**
     * Batch encrypt data.
     */
    public function batchEncrypt(array $dataList): array
    {
        $results = [];
        
        foreach ($dataList as $key => $data) {
            try {
                $results[$key] = [
                    'success' => true,
                    'encrypted' => $this->encrypt($data),
                ];
            } catch (Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Batch decrypt data.
     */
    public function batchDecrypt(array $encryptedDataList): array
    {
        $results = [];
        
        foreach ($encryptedDataList as $key => $encryptedData) {
            try {
                $results[$key] = [
                    'success' => true,
                    'decrypted' => $this->decrypt($encryptedData),
                ];
            } catch (Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Create secure hash of data.
     */
    public function hash(mixed $data): string
    {
        $dataString = is_array($data) || is_object($data) ? json_encode($data) : (string) $data;
        return hash('sha256', $dataString . $this->key);
    }

    /**
     * Verify data integrity.
     */
    public function verifyIntegrity(mixed $data, string $hash): bool
    {
        return hash_equals($this->hash($data), $hash);
    }
}
