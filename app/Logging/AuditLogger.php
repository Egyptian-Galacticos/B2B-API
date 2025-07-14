<?php

namespace App\Logging;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class AuditLogger
{
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'phone_number',
        'address',
        'street',
        'city',
        'postal_code',
        'country',
        'credit_card',
        'ssn',
        'date_of_birth',
        'api_token',
        'access_token',
        'session_id',
        'passport_number',
        'drivers_license',
        'name',
        'tax_id',
        'company_phone',
        'description',
        'shipping_address',
        'billing_address',
        'terms_and_conditions',
        'specifications',
    ];

    /**
     * Sanitize metadata by replacing sensitive fields with [REDACTED].
     */
    public function sanitizeMetadata(array $metadata): array
    {
        foreach ($this->sensitiveKeys as $key) {
            if (isset($metadata[$key])) {

                $metadata[$key] = '[REDACTED]';

            }
        }

        return $metadata;
    }

    public function __invoke(array $config): Logger
    {
        $logger = new Logger('audit');

        $logger->pushHandler(new class extends AbstractProcessingHandler
        {
            protected function write(LogRecord $record): void
            {
                $context = $record['context'];
                $sanitizedMetadata = (new AuditLogger)->sanitizeMetadata($context['metadata'] ?? []);

                AuditLog::create([
                    'user_id'     => $context['user_id'] ?? Auth::id(),
                    'action'      => $record['message'],
                    'entity_type' => $context['entity_type'] ?? null,
                    'entity_id'   => $context['entity_id'] ?? null,
                    'ip_address'  => $context['ip_address'] ?? Request::ip(),
                    'user_agent'  => $context['user_agent'] ?? Request::userAgent(),
                    'metadata'    => $sanitizedMetadata ?? [],
                    'created_at'  => now(),
                ]);
            }
        });

        return $logger;
    }
}
