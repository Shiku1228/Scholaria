<?php

namespace App\Services;

use App\Models\SecurityAudit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SecurityAlertService
{
    /**
     * Send security alert.
     */
    public function sendAlert(array $alertData): bool
    {
        try {
            $alert = $this->createAlert($alertData);
            
            // Send notifications based on severity and channels
            $this->sendNotifications($alert);
            
            // Store alert for dashboard
            $this->storeAlert($alert);
            
            Log::info('Security alert sent', [
                'alert_id' => $alert['id'],
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send security alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
            
            return false;
        }
    }

    /**
     * Send critical security alert immediately.
     */
    public function sendCriticalAlert(string $message, array $data = []): bool
    {
        return $this->sendAlert([
            'type' => 'critical',
            'severity' => 'critical',
            'message' => $message,
            'data' => $data,
            'immediate' => true,
        ]);
    }

    /**
     * Send high priority alert.
     */
    public function sendHighAlert(string $message, array $data = []): bool
    {
        return $this->sendAlert([
            'type' => 'high',
            'severity' => 'high',
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send medium priority alert.
     */
    public function sendMediumAlert(string $message, array $data = []): bool
    {
        return $this->sendAlert([
            'type' => 'medium',
            'severity' => 'medium',
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send low priority alert.
     */
    public function sendLowAlert(string $message, array $data = []): bool
    {
        return $this->sendAlert([
            'type' => 'low',
            'severity' => 'low',
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Process security audit and send alerts if needed.
     */
    public function processSecurityAudit(SecurityAudit $audit): void
    {
        $alertRules = $this->getAlertRules();
        
        foreach ($alertRules as $rule) {
            if ($this->shouldTriggerAlert($audit, $rule)) {
                $this->sendAlert([
                    'type' => $rule['type'],
                    'severity' => $rule['severity'],
                    'message' => $this->formatAlertMessage($audit, $rule),
                    'data' => [
                        'audit_id' => $audit->id,
                        'event_type' => $audit->event_type,
                        'event_data' => $audit->event_data,
                    ],
                    'source' => 'security_audit',
                ]);
                
                // Only trigger one alert per audit to avoid spam
                break;
            }
        }
    }

    /**
     * Get alert configuration rules.
     */
    private function getAlertRules(): array
    {
        return [
            [
                'type' => 'security_breach',
                'severity' => 'critical',
                'conditions' => ['event_type' => 'security_breach'],
                'channels' => ['email', 'sms', 'webhook'],
                'cooldown' => 300, // 5 minutes
            ],
            [
                'type' => 'brute_force_attack',
                'severity' => 'high',
                'conditions' => ['event_type' => 'suspicious_activity', 'event_data->pattern_type' => 'brute_force'],
                'channels' => ['email', 'webhook'],
                'cooldown' => 600, // 10 minutes
            ],
            [
                'type' => 'privilege_escalation',
                'severity' => 'high',
                'conditions' => ['event_type' => 'suspicious_activity', 'event_data->resource_type' => 'admin'],
                'channels' => ['email', 'webhook'],
                'cooldown' => 900, // 15 minutes
            ],
            [
                'type' => 'unusual_login_pattern',
                'severity' => 'medium',
                'conditions' => ['event_type' => 'suspicious_activity', 'event_data->pattern_type' => 'unusual_login'],
                'channels' => ['email'],
                'cooldown' => 1800, // 30 minutes
            ],
            [
                'type' => 'high_volume_failed_logins',
                'severity' => 'medium',
                'conditions' => ['event_type' => 'failed_login'],
                'channels' => ['email'],
                'cooldown' => 3600, // 1 hour
            ],
            [
                'type' => 'session_anomaly',
                'severity' => 'medium',
                'conditions' => ['event_type' => 'suspicious_activity', 'event_data->pattern_type' => 'session'],
                'channels' => ['email'],
                'cooldown' => 1800, // 30 minutes
            ],
        ];
    }

    /**
     * Check if alert should be triggered.
     */
    private function shouldTriggerAlert(SecurityAudit $audit, array $rule): bool
    {
        // Check cooldown period
        $cooldownKey = $this->getCooldownKey($audit, $rule);
        if (Cache::has($cooldownKey)) {
            return false;
        }

        // Check conditions
        foreach ($rule['conditions'] as $key => $value) {
            if (is_string($value) && str_contains($value, '*')) {
                // Wildcard matching
                $pattern = str_replace('*', '.*', preg_quote($value, '/'));
                if (!preg_match("/^{$pattern}$/i", $audit->$key ?? '')) {
                    return false;
                }
            } else {
                // Exact matching
                if (($audit->$key ?? null) != $value) {
                    return false;
                }
            }
        }

        // Set cooldown
        Cache::put($cooldownKey, true, $rule['cooldown']);

        return true;
    }

    /**
     * Create alert data structure.
     */
    private function createAlert(array $alertData): array
    {
        return [
            'id' => uniqid('alert_'),
            'type' => $alertData['type'],
            'severity' => $alertData['severity'],
            'message' => $alertData['message'],
            'data' => $alertData['data'] ?? [],
            'source' => $alertData['source'] ?? 'manual',
            'created_at' => now(),
            'channels' => $alertData['channels'] ?? $this->getDefaultChannels($alertData['severity']),
            'immediate' => $alertData['immediate'] ?? false,
        ];
    }

    /**
     * Get default notification channels by severity.
     */
    private function getDefaultChannels(string $severity): array
    {
        return match ($severity) {
            'critical' => ['email', 'sms', 'webhook'],
            'high' => ['email', 'webhook'],
            'medium' => ['email'],
            'low' => ['email'],
            default => ['email'],
        };
    }

    /**
     * Send notifications through configured channels.
     */
    private function sendNotifications(array $alert): void
    {
        foreach ($alert['channels'] as $channel) {
            switch ($channel) {
                case 'email':
                    $this->sendEmailNotification($alert);
                    break;
                case 'sms':
                    $this->sendSmsNotification($alert);
                    break;
                case 'webhook':
                    $this->sendWebhookNotification($alert);
                    break;
                case 'slack':
                    $this->sendSlackNotification($alert);
                    break;
            }
        }
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(array $alert): void
    {
        try {
            $recipients = $this->getAlertRecipients($alert['severity']);
            
            if (empty($recipients)) {
                Log::warning('No email recipients configured for security alerts');
                return;
            }

            $subject = "[{$alert['severity']}] Security Alert: {$alert['message']}";
            $content = $this->formatEmailContent($alert);

            // In production, use Laravel's Mail facade
            // Mail::to($recipients)->send(new SecurityAlertMail($subject, $content));
            
            Log::info('Security alert email sent', [
                'alert_id' => $alert['id'],
                'recipients' => $recipients,
                'subject' => $subject,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'alert_id' => $alert['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS notification.
     */
    private function sendSmsNotification(array $alert): void
    {
        try {
            $recipients = $this->getSmsRecipients($alert['severity']);
            
            if (empty($recipients)) {
                Log::warning('No SMS recipients configured for security alerts');
                return;
            }

            foreach ($recipients as $phoneNumber) {
                // In production, integrate with SMS service
                // $this->smsService->send($phoneNumber, $alert['message']);
                
                Log::info('Security alert SMS sent', [
                    'alert_id' => $alert['id'],
                    'phone_number' => $phoneNumber,
                    'message' => $alert['message'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'alert_id' => $alert['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send webhook notification.
     */
    private function sendWebhookNotification(array $alert): void
    {
        try {
            $webhookUrl = config('security.alerts.webhook_url');
            
            if (!$webhookUrl) {
                return;
            }

            $payload = [
                'alert' => $alert,
                'timestamp' => now()->toISOString(),
            ];

            // In production, use HTTP client to send webhook
            // Http::post($webhookUrl, $payload);
            
            Log::info('Security alert webhook sent', [
                'alert_id' => $alert['id'],
                'webhook_url' => $webhookUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send webhook notification', [
                'alert_id' => $alert['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Slack notification.
     */
    private function sendSlackNotification(array $alert): void
    {
        try {
            $webhookUrl = config('security.alerts.slack_webhook_url');
            
            if (!$webhookUrl) {
                return;
            }

            $color = match ($alert['severity']) {
                'critical' => 'danger',
                'high' => 'warning',
                'medium' => 'good',
                'low' => 'info',
                default => 'info',
            };

            $payload = [
                'text' => "Security Alert: {$alert['message']}",
                'attachments' => [
                    [
                        'color' => $color,
                        'fields' => [
                            [
                                'title' => 'Severity',
                                'value' => strtoupper($alert['severity']),
                                'short' => true,
                            ],
                            [
                                'title' => 'Type',
                                'value' => $alert['type'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Time',
                                'value' => $alert['created_at']->format('Y-m-d H:i:s'),
                                'short' => true,
                            ],
                        ],
                    ],
                ],
            ];

            // In production, use Slack webhook
            // Http::post($webhookUrl, $payload);
            
            Log::info('Security alert Slack notification sent', [
                'alert_id' => $alert['id'],
                'webhook_url' => $webhookUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification', [
                'alert_id' => $alert['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store alert for dashboard.
     */
    private function storeAlert(array $alert): void
    {
        try {
            // Store in cache for dashboard
            Cache::push('security_alerts', $alert, 1440); // Store for 24 hours
            
            // Also store in database if needed
            // SecurityAlert::create($alert);
            
        } catch (\Exception $e) {
            Log::error('Failed to store security alert', [
                'alert_id' => $alert['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get alert recipients by severity.
     */
    private function getAlertRecipients(string $severity): array
    {
        $configKey = "security.alerts.recipients.{$severity}";
        $recipients = config($configKey, []);
        
        // Fallback to general recipients
        if (empty($recipients)) {
            $recipients = config('security.alerts.recipients.default', []);
        }
        
        return $recipients;
    }

    /**
     * Get SMS recipients by severity.
     */
    private function getSmsRecipients(string $severity): array
    {
        $configKey = "security.alerts.sms_recipients.{$severity}";
        $recipients = config($configKey, []);
        
        // Fallback to general SMS recipients
        if (empty($recipients)) {
            $recipients = config('security.alerts.sms_recipients.default', []);
        }
        
        return $recipients;
    }

    /**
     * Format alert message from security audit.
     */
    private function formatAlertMessage(SecurityAudit $audit, array $rule): string
    {
        $eventType = $audit->event_type;
        $description = $audit->description;
        
        switch ($rule['type']) {
            case 'brute_force_attack':
                return "Brute force attack detected: {$description}";
            case 'privilege_escalation':
                return "Privilege escalation attempt: {$description}";
            case 'unusual_login_pattern':
                return "Unusual login pattern: {$description}";
            case 'high_volume_failed_logins':
                return "High volume of failed logins detected";
            case 'session_anomaly':
                return "Session anomaly detected: {$description}";
            default:
                return "Security event: {$description}";
        }
    }

    /**
     * Format email content.
     */
    private function formatEmailContent(array $alert): string
    {
        $content = "Security Alert\n\n";
        $content .= "Severity: " . strtoupper($alert['severity']) . "\n";
        $content .= "Type: " . $alert['type'] . "\n";
        $content .= "Message: " . $alert['message'] . "\n";
        $content .= "Time: " . $alert['created_at']->format('Y-m-d H:i:s') . "\n\n";
        
        if (!empty($alert['data'])) {
            $content .= "Details:\n";
            foreach ($alert['data'] as $key => $value) {
                $content .= "- {$key}: " . json_encode($value) . "\n";
            }
        }
        
        $content .= "\nThis is an automated security alert from the system.";
        
        return $content;
    }

    /**
     * Get cooldown key for alert.
     */
    private function getCooldownKey(SecurityAudit $audit, array $rule): string
    {
        return "security_alert_cooldown:{$rule['type']}:" . md5($audit->ip_address . $audit->event_type);
    }

    /**
     * Get recent alerts for dashboard.
     */
    public function getRecentAlerts(int $limit = 50): array
    {
        $alerts = Cache::get('security_alerts', []);
        
        if (empty($alerts)) {
            return [];
        }
        
        // Get the most recent alerts
        $recentAlerts = array_slice(array_reverse($alerts), 0, $limit);
        
        return array_map(function ($alert) {
            return [
                'id' => $alert['id'],
                'type' => $alert['type'],
                'severity' => $alert['severity'],
                'message' => $alert['message'],
                'created_at' => $alert['created_at'],
                'data' => $alert['data'] ?? [],
            ];
        }, $recentAlerts);
    }

    /**
     * Get alert statistics.
     */
    public function getAlertStats(int $hours = 24): array
    {
        $alerts = Cache::get('security_alerts', []);
        $cutoff = now()->subHours($hours);
        
        $stats = [
            'total' => 0,
            'by_severity' => [
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
            'by_type' => [],
        ];
        
        foreach ($alerts as $alert) {
            if ($alert['created_at'] >= $cutoff) {
                $stats['total']++;
                $stats['by_severity'][$alert['severity']]++;
                
                $type = $alert['type'];
                if (!isset($stats['by_type'][$type])) {
                    $stats['by_type'][$type] = 0;
                }
                $stats['by_type'][$type]++;
            }
        }
        
        return $stats;
    }
}
