<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array connect(string $integrationId)
 * @method static array status(string $integrationId)
 * @method static array sendMessage(string $integrationId, string $recipient, string $message)
 * @method static array disconnect(string $integrationId)
 *
 * @see \App\Services\WhatsApp\WhatsAppService
 */
class WhatsApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp';
    }
}