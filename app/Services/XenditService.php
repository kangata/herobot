<?php

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\GetInvoiceRequest;
use Xendit\XenditSdkException;

class XenditService
{
    protected $apiInstance;

    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->apiInstance = new InvoiceApi();
    }

    public function createInvoice(array $params)
    {
        try {
            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => $params['external_id'],
                'description' => $params['description'], 
                'amount' => $params['amount'],
                'invoice_duration' => 86400, // 24 hours
                'currency' => 'IDR',
                'reminder_time' => 1,
                'payer_email' => $params['payer_email'],
                'success_redirect_url' => $params['success_redirect_url'],
                'failure_redirect_url' => $params['failure_redirect_url'],
                'should_send_email' => true,
                'customer' => [
                    'email' => $params['payer_email'],
                    'given_names' => $params['customer_name'] ?? null,
                ]
            ]);

            return $this->apiInstance->createInvoice($createInvoiceRequest);

        } catch (XenditSdkException $e) {
            throw $e;
        }
    }

    public function getInvoice(string $invoiceId)
    {
        try {
            return $this->apiInstance->getInvoiceById($invoiceId);
        } catch (XenditSdkException $e) {
            throw $e;
        }
    }

    public function validateCallback(string $callbackToken): bool
    {
        return $callbackToken === config('services.xendit.webhook_token');
    }
}
