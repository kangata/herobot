<?php

namespace App\Http\Controllers;

use App\Services\TokenPricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingController extends Controller
{
    protected TokenPricingService $tokenPricingService;

    public function __construct(TokenPricingService $tokenPricingService)
    {
        $this->tokenPricingService = $tokenPricingService;

        if (config('app.edition') !== 'cloud') {
            abort(404);
        }
    }

    /**
     * Display the pricing page with token pricing for all models.
     */
    public function index(Request $request)
    {
        // Get all pricing data
        $allPricing = $this->tokenPricingService->getAllPricing();
        
        // Transform pricing data to include both USD and credits
        $pricingData = [];
        
        foreach ($allPricing as $provider => $models) {
            $pricingData[$provider] = [];
            
            foreach ($models as $modelName => $pricing) {
                $isAudioModel = $this->tokenPricingService->isAudioModel($provider, $modelName);
                
                $pricingData[$provider][$modelName] = [
                    'input_usd' => $pricing['input'],
                    'output_usd' => $pricing['output'],
                    'input_credits' => $this->tokenPricingService->usdToCredits($pricing['input']),
                    'output_credits' => $this->tokenPricingService->usdToCredits($pricing['output']),
                    'is_audio' => $isAudioModel,
                    'type' => $pricing['type'] ?? 'text',
                ];
            }
        }

        $aiModelService = new \App\Services\AIModelService();
        
        return Inertia::render('Pricing/Index', [
            'pricing' => $pricingData,
            'exchange_rate' => [
                'usd_to_credits' => 16500,
                'credits_to_usd' => 1 / 16500,
            ],
            'aiModels' => $aiModelService->getModelConfigForFrontend(),
        ]);
    }
}
