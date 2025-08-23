<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Model Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all available AI models
    | across different providers and services. It serves as the single
    | source of truth for model definitions, pricing, and capabilities.
    |
    */

    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'models' => [
                // GPT-5 Series
                'gpt-5' => [
                    'name' => 'GPT-5',
                    'services' => ['chat'],
                    'pricing' => ['input' => 1.25, 'output' => 10.0],
                ],
                'gpt-5-mini' => [
                    'name' => 'GPT-5 Mini',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.25, 'output' => 2.0],
                ],
                'gpt-5-nano' => [
                    'name' => 'GPT-5 Nano',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.05, 'output' => 0.4],
                ],

                // GPT-4.1 Series
                'gpt-4.1' => [
                    'name' => 'GPT-4.1',
                    'services' => ['chat'],
                    'pricing' => ['input' => 2.0, 'output' => 8.0],
                ],
                'gpt-4.1-mini' => [
                    'name' => 'GPT-4.1 Mini',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.4, 'output' => 1.6],
                ],
                'gpt-4.1-nano' => [
                    'name' => 'GPT-4.1 Nano',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.1, 'output' => 0.4],
                ],

                // GPT-4o Series
                'gpt-4o' => [
                    'name' => 'GPT-4o',
                    'services' => ['chat'],
                    'pricing' => ['input' => 2.5, 'output' => 10.0],
                ],
                'gpt-4o-mini' => [
                    'name' => 'GPT-4o Mini',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.15, 'output' => 0.6],
                ],

                // Audio Models
                'whisper-1' => [
                    'name' => 'Whisper-1',
                    'services' => ['speech'],
                    'pricing' => ['input' => 0.006, 'output' => 0],
                    'type' => 'audio',
                ],

                // Embeddings
                'text-embedding-3-small' => [
                    'name' => 'Text Embedding 3 Small',
                    'services' => ['embedding'],
                    'pricing' => ['input' => 0.01, 'output' => 0],
                ],
                'text-embedding-3-large' => [
                    'name' => 'Text Embedding 3 Large',
                    'services' => ['embedding'],
                    'pricing' => ['input' => 0.065, 'output' => 0],
                ],
                'text-embedding-ada-002' => [
                    'name' => 'Text Embedding Ada 002',
                    'services' => ['embedding'],
                    'pricing' => ['input' => 0.05, 'output' => 0],
                ],
            ],
        ],

        'gemini' => [
            'name' => 'Google Gemini',
            'models' => [
                // Gemini 2.5 Series
                'gemini-2.5-pro' => [
                    'name' => 'Gemini 2.5 Pro',
                    'services' => ['chat'],
                    'pricing' => ['input' => 1.25, 'output' => 10.0],
                ],
                'gemini-2.5-flash' => [
                    'name' => 'Gemini 2.5 Flash',
                    'services' => ['chat', 'speech'],
                    'pricing' => ['input' => 0.3, 'output' => 2.5],
                ],
                'gemini-2.5-flash-lite' => [
                    'name' => 'Gemini 2.5 Flash Lite',
                    'services' => ['chat'],
                    'pricing' => ['input' => 0.1, 'output' => 0.4],
                ],

                // Embeddings
                'gemini-embedding-001' => [
                    'name' => 'Gemini Embedding 001',
                    'services' => ['embedding'],
                    'pricing' => ['input' => 0.15, 'output' => 0],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Types
    |--------------------------------------------------------------------------
    |
    | Define the available service types and their configurations.
    |
    */
    'services' => [
        'chat' => [
            'name' => 'Chat Service',
            'description' => 'Text generation and conversation models',
        ],
        'embedding' => [
            'name' => 'Embedding Service',
            'description' => 'Text embedding and similarity models',
        ],
        'speech' => [
            'name' => 'Speech-to-Text Service',
            'description' => 'Audio transcription and speech recognition models',
        ],
    ],
];
