<?php

namespace App\Services;

class AIModelService
{
    /**
     * Get AI model configuration formatted for frontend.
     */
    public function getModelConfigForFrontend(): array
    {
        $config = config('ai-models');
        $providers = [];
        
        foreach ($config['providers'] as $providerId => $provider) {
            $providerData = [
                'id' => $providerId,
                'name' => $provider['name'],
                'models' => []
            ];
            
            foreach ($provider['models'] as $modelId => $model) {
                $providerData['models'][$modelId] = [
                    'id' => $modelId,
                    'name' => $model['name'],
                    'services' => $model['services'],
                ];
            }
            
            $providers[$providerId] = $providerData;
        }
        
        return [
            'providers' => $providers,
            'services' => $config['services'],
        ];
    }
}
