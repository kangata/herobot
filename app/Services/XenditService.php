<?php

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
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
                'invoice_duration' => 172800,
                'currency' => 'IDR',
                'reminder_time' => 1,
                'payer_email' => $params['payer_email'],
                'success_redirect_url' => $params['success_redirect_url'],
                'failure_redirect_url' => $params['failure_redirect_url']
            ]);

            return $this->apiInstance->createInvoice($createInvoiceRequest);

        } catch (XenditSdkException $e) {
            throw $e;
        }
    }
}
