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
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('audit');

        $logger->pushHandler(new class extends AbstractProcessingHandler
        {
            protected function write(LogRecord $record): void
            {
                $context = $record['context'];

                AuditLog::create([
                    'user_id' => $context['user_id'] ?? Auth::id(),
                    'action' => $record['message'],
                    'entity_type' => $context['entity_type'] ?? null,
                    'entity_id' => $context['entity_id'] ?? null,
                    'ip_address' => $context['ip_address'] ?? Request::ip(),
                    'user_agent' => $context['user_agent'] ?? Request::userAgent(),
                    'metadata' => $context['metadata'] ?? [],
                    'created_at' => now(),
                ]);
            }
        });

        return $logger;
    }
}
