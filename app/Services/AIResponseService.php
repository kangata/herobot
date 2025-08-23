<?php

namespace App\Services;

use App\Services\AIServiceFactory;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Services\Contracts\ChatServiceInterface;
use App\Services\TokenPricingService;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\ChatMedia;
use App\Models\Tool;
use App\Models\ChatHistory;
use App\Models\TokenUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * AI Response Service
 * 
 * Handles AI response generation, tool calling, knowledge search,
 * and chat history management for bots.
 */
class AIResponseService
{
    protected ToolService $toolService;
    protected TokenPricingService $tokenPricingService;
    protected bool $toolCallingEnabled = true;

    /**
     * Constructor
     *
     * @param ToolService $toolService Service for handling tool execution
     * @param TokenPricingService $tokenPricingService Service for calculating token costs
     */
    public function __construct(ToolService $toolService, TokenPricingService $tokenPricingService)
    {
        $this->toolService = $toolService;
        $this->tokenPricingService = $tokenPricingService;
    }
    /**
     * Generate AI response for a bot with message and chat history.
     *
     * @param Bot $bot Bot instance with prompt property
     * @param Channel $channel Channel instance
     * @param string $message Latest message from user
     * @param string $sender Sender identifier
     * @param ChatMedia|null $media Media data (optional)
     * @param string $format Output format: 'whatsapp' or 'html'
     * @return string|false Formatted response string or false on failure
     */
    public function generateResponse(Bot $bot, ?Channel $channel, string $message, string $sender, ?ChatMedia $media = null, string $format = 'whatsapp'): string|false
    {
        try {
            // Get chat history from database
            $chatHistory = $this->getChatHistory($bot->id, $channel->id ?? null, $sender, 5);
            
            // Save user message to chat history
            $this->saveChatHistory([
                'channel_id' => $channel->id ?? null,
                'bot_id' => $bot->id,
                'sender' => $sender,
                'message' => $message,
                'role' => 'user',
                'message_type' => $media ? 'media' : 'text',
                'media_data' => $media ? [
                    'mime_type' => $media->mime_type,
                    'data' => $media->getData()
                ] : null,
                'metadata' => [
                    'format' => $format,
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            // Get separately configured services
            $chatService = AIServiceFactory::createChatService();
            $embeddingService = AIServiceFactory::createEmbeddingService();
            
            // Search for relevant knowledge using embedding service
            $embeddingResult = $this->searchSimilarKnowledge($embeddingService, $message, $bot, 3);
            $relevantKnowledge = $embeddingResult['knowledge'];
            $embeddingTokenUsage = $embeddingResult['token_usage'] ?? null;
            
            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($bot, $relevantKnowledge);
            
            // Build messages array
            $messages = $this->buildMessagesArray($systemPrompt, $chatHistory, $message);
            
            // Get available tools for the bot if tool calling is enabled
            $tools = $this->toolCallingEnabled 
                ? $this->getAvailableToolsForBot($bot)
                : [];
            
            // Generate response using chat service
            $startTime = microtime(true);
            $response = $chatService->generateResponse(
                $messages,
                null, // model parameter
                $media ? $media->getData() : null,
                $media ? $media->mime_type : null,
                $tools
            );
            $endTime = microtime(true);
            $responseTime = $endTime - $startTime;

            $toolCalls = null;
            $toolResponses = null;
            $rawContent = null;
            $chatTokenUsage = $response['token_usage'] ?? null;
            $finalTokenUsage = null;
            
            // Handle tool calls if present in the response
            if (is_array($response) && isset($response['tool_calls']) && !empty($response['tool_calls'])) {
                $toolCalls = $response['tool_calls'];
                
                // Save assistant message with tool calls first
                $this->saveChatHistory([
                    'channel_id' => $channel->id ?? null,
                    'bot_id' => $bot->id,
                    'sender' => $sender,
                    'message' => $response['content'] ?? '',
                    'role' => 'assistant',
                    'message_type' => 'tool_call',
                    'tool_calls' => $toolCalls,
                    'raw_content' => $response['content'] ?? '',
                    'metadata' => [
                        'format' => $format,
                        'model_used' => get_class($chatService),
                        'timestamp' => now()->toISOString(),
                        'has_tool_calls' => true
                    ]
                ]);
                
                $toolResponses = $this->handleToolCalls($response['tool_calls'], $bot, $channel->id ?? null, $sender);

                // Add assistant message with tool calls
                $messages[] = [
                    'role' => 'assistant', 
                    'content' => $response['content'] ?? null,
                    'tool_calls' => $response['tool_calls']
                ];
                
                // Add tool responses
                $messages = array_merge($messages, $toolResponses);
                
                // Generate final response
                $finalStartTime = microtime(true);
                $finalResponse = $chatService->generateResponse($messages, null, null, null, []);
                $finalEndTime = microtime(true);
                $finalResponseTime = $finalEndTime - $finalStartTime;
                
                $responseContent = is_array($finalResponse) ? ($finalResponse['content'] ?? '') : $finalResponse;
                $rawContent = $responseContent;
                $finalTokenUsage = $finalResponse['token_usage'] ?? null;
                
                // Combine token usage from both calls
                if ($chatTokenUsage && $finalTokenUsage) {
                    $chatTokenUsage['input_tokens'] += $finalTokenUsage['input_tokens'];
                    $chatTokenUsage['output_tokens'] += $finalTokenUsage['output_tokens'];
                    $chatTokenUsage['total_tokens'] += $finalTokenUsage['total_tokens'];
                }
                $responseTime += $finalResponseTime;
            } else {
                $responseContent = is_array($response) ? ($response['content'] ?? '') : $response;
                $rawContent = $responseContent;
            }

            // Format response based on the specified format
            $formattedResponse = '';
            if ($format === 'html') {
                $formattedResponse = $this->convertMarkdownToHtml($responseContent);
            } else {
                $formattedResponse = $this->convertMarkdownToWhatsApp($responseContent);
            }
            
            // Record token usage and calculate costs
            $this->recordTokenUsage($bot, $chatService, $chatTokenUsage, $responseTime);
            if ($embeddingTokenUsage) {
                $this->recordTokenUsage($bot, $embeddingService, $embeddingTokenUsage, 0, 'embedding');
            }
            
            // Save assistant response to chat history
            $this->saveChatHistory([
                'channel_id' => $channel->id ?? null,
                'bot_id' => $bot->id,
                'sender' => $sender,
                'message' => $formattedResponse,
                'role' => 'assistant',
                'message_type' => 'text',
                'raw_content' => $rawContent,
                'metadata' => [
                    'format' => $format,
                    'model_used' => get_class($chatService),
                    'timestamp' => now()->toISOString(),
                    'knowledge_used' => $relevantKnowledge->isNotEmpty(),
                    'token_usage' => $chatTokenUsage,
                    'embedding_token_usage' => $embeddingTokenUsage
                ]
            ]);
            
            return $formattedResponse;
        } catch (\Exception $e) {
            Log::error('Failed to generate response: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build system prompt with bot prompt and relevant knowledge.
     *
     * @param Bot $bot Bot instance with prompt property
     * @param Collection $relevantKnowledge Collection of relevant knowledge items
     * @return string Complete system prompt
     */
    private function buildSystemPrompt(Bot $bot, Collection $relevantKnowledge): string
    {
        $systemPrompt = $bot->prompt;
        
        if ($relevantKnowledge->isNotEmpty()) {
            $systemPrompt .= "\n\nGunakan informasi berikut untuk menjawab pertanyaan:\n\n";
            foreach ($relevantKnowledge as $knowledge) {
                $systemPrompt .= "{$knowledge['text']}\n\n";
            }
        } else {
            $systemPrompt .= "\n\nTidak ada informasi spesifik yang ditemukan dalam basis pengetahuan.";
        }
        
        return $systemPrompt;
    }

    /**
     * Build messages array from system prompt, chat history, and current message.
     *
     * @param string $systemPrompt System prompt text
     * @param Collection $chatHistory Collection of chat history items
     * @param string $message Current user message
     * @return array Array of messages formatted for AI service
     */
    private function buildMessagesArray(string $systemPrompt, Collection $chatHistory, string $message): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add chat history using role and message fields
        foreach ($chatHistory as $ch) {
            if (is_object($ch)) {
                // Object format - use role and message fields
                $messageData = ['role' => $ch->role, 'content' => $ch->message];
                
                // Add tool_call_id for tool role messages
                if ($ch->role === 'tool' && $ch->tool_call_id) {
                    $messageData['tool_call_id'] = $ch->tool_call_id;
                }
                
                // Add tool_calls for assistant messages that have them
                if ($ch->role === 'assistant' && $ch->tool_calls) {
                    $messageData['tool_calls'] = $ch->tool_calls;
                }
                
                $messages[] = $messageData;
            } else {
                // Array format fallback for legacy data
                $messages[] = ['role' => 'user', 'content' => $ch['message']];
                if (isset($ch['response'])) {
                    $messages[] = ['role' => 'assistant', 'content' => $ch['response']];
                }
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    /**
     * Convert markdown formatting to HTML.
     *
     * @param string $text Markdown text to convert
     * @return string HTML formatted text
     */
    public function convertMarkdownToHtml(string $text): string
    {
        // Convert headers: # text to <h1>text</h1>, ## text to <h2>text</h2>, etc.
        $text = preg_replace_callback('/^(#{1,6})\s+(.*)$/m', function($matches) {
            $level = strlen($matches[1]);
            return "<h{$level}>{$matches[2]}</h{$level}>";
        }, $text);

        // Convert bold: **text** or __text__ to <strong>text</strong>
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $text);

        // Convert italic: *text* or _text_ to <em>text</em>
        $text = preg_replace('/(?<!\*)\*(?!\*)([^*]+?)(?<!\*)\*(?!\*)|_([^_]+?)_/', '<em>$1$2</em>', $text);

        // Convert strikethrough: ~~text~~ to <del>text</del>
        $text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);

        // Convert inline code: `text` to <code>text</code>
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Convert bullet points: - text to <ul><li>text</li></ul>
        $text = preg_replace_callback('/^- (.*)$/m', function($matches) {
            return '<li>' . $matches[1] . '</li>';
        }, $text);

        // Wrap consecutive <li> elements in <ul> tags
        $text = preg_replace_callback('/(<li>.*<\/li>)(?:\n<li>.*<\/li>)*/s', function($matches) {
            return '<ul>' . $matches[0] . '</ul>';
        }, $text);

        // Convert links: [text](url) to <a href="url">text</a>
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $text);

        // Convert line breaks to <br> tags
        $text = nl2br($text);

        return $text;
    }

    /**
     * Convert markdown formatting to WhatsApp-compatible formatting.
     *
     * @param string $text Markdown text to convert
     * @return string WhatsApp formatted text
     */
    public function convertMarkdownToWhatsApp(string $text): string
    {
        // Convert italic: *text* or _text_ to _text_
        $text = preg_replace('/(?<!\*)\*(?!\*)(\S+?)(?<!\*)\*(?!\*)|_(\S+?)_/', '_$1$2_', $text);

        // Convert bold: **text** or __text__ to *text*
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '*$2*', $text);

        // Convert strikethrough: ~~text~~ to ~text~
        $text = preg_replace('/~~(.*?)~~/', '~$1~', $text);

        // Convert inline code: `text` to ```text```
        $text = preg_replace('/`([^`]+)`/', '```$1```', $text);

        // Convert bullet points: - text to • text
        $text = preg_replace('/^- /m', '• ', $text);

        // Convert links: [text](url) to text: url
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '$2', $text);

        // Convert headers: # text to text
        $text = preg_replace('/^#+\s+(.*)$/m', '*$1*', $text);

        return $text;
    }

    /**
     * Search for similar knowledge using embedding service.
     *
     * @param EmbeddingServiceInterface $embeddingService Embedding service instance
     * @param string $query Search query
     * @param Bot $bot Bot instance
     * @param int $limit Maximum number of results to return
     * @return array Array containing knowledge collection and token usage
     */
    public function searchSimilarKnowledge(EmbeddingServiceInterface $embeddingService, string $query, Bot $bot, int $limit = 3): array
    {
        try {
            // Create embedding for the query
            $embeddingResult = $embeddingService->createEmbedding($query);
            $queryEmbedding = $embeddingResult['embeddings'][0] ?? $embeddingResult;
            $tokenUsage = $embeddingResult['token_usage'] ?? null;

            // Get only necessary vectors with optimized query
            $knowledgeVectors = $bot->knowledge()
                ->where('status', 'completed')
                ->with(['vectors:id,knowledge_id,text,vector'])
                ->get()
                ->flatMap(function ($knowledge) use ($queryEmbedding) {
                    return $knowledge->vectors->map(function ($vector) use ($queryEmbedding) {
                        return [
                            'text' => $vector->text,
                            'similarity' => $this->calculateSimilarity($queryEmbedding, $vector->vector),
                        ];
                    });
                });

            // Sort and limit results
            $knowledge = $knowledgeVectors->sortByDesc('similarity')
                ->take($limit)
                ->values();

            return [
                'knowledge' => $knowledge,
                'token_usage' => $tokenUsage
            ];

        } catch (\Exception $e) {
            Log::error('Error searching similar knowledge: ' . $e->getMessage());
            return [
                'knowledge' => collect(),
                'token_usage' => null
            ];
        }
    }

    /**
     * Calculate similarity between two vectors using fast C extension if available,
     * otherwise fallback to PHP implementation.
     *
     * @param array $vector1 First vector
     * @param array $vector2 Second vector
     * @return float Similarity score between 0 and 1
     */
    protected function calculateSimilarity(array $vector1, array $vector2): float
    {
        if (function_exists('fast_cosine_similarity')) {
            return fast_cosine_similarity($vector1, $vector2);
        }

        return $this->cosineSimilarity($vector1, $vector2);
    }

    /**
     * Calculate cosine similarity between two vectors using PHP implementation.
     *
     * @param array $vector1 First vector
     * @param array $vector2 Second vector
     * @return float Cosine similarity score between -1 and 1
     */
    protected function cosineSimilarity(array $vector1, array $vector2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($vector1 as $i => $value) {
            $dotProduct += $value * $vector2[$i];
            $norm1 += $value * $value;
            $norm2 += $vector2[$i] * $vector2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        return $dotProduct / ($norm1 * $norm2);
    }

    /**
     * Get available tools for a bot.
     *
     * @param Bot $bot Bot instance with team_id property
     * @return array Array of formatted tools for AI service
     */
    protected function getAvailableToolsForBot(Bot $bot): array
    {
        $tools = Tool::where('team_id', $bot->team_id)
            ->where('is_active', true)
            ->get();
        
        return $tools->map(function ($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $this->sanitizeFunctionName($tool->id, $tool->name),
                    'description' => $tool->description,
                    'parameters' => $tool->parameters_schema,
                ],
            ];
        })->toArray();
    }

    /**
     * Handle tool calls from AI response.
     *
     * @param array $toolCalls Array of tool calls from AI response
     * @param Bot $bot Bot instance
     * @param int|null $channelId Channel ID (optional)
     * @param string|null $sender Sender identifier (optional)
     * @return array Array of tool responses
     */
    protected function handleToolCalls(array $toolCalls, Bot $bot, ?int $channelId = null, ?string $sender = null): array
    {
        $toolResponses = [];
        
        foreach ($toolCalls as $toolCall) {
            $toolCallId = $toolCall['id'];
            $toolName = $toolCall['function']['name'];

            $toolId = preg_match('/_(\d+)$/', $toolName, $matches) ? $matches[1] : null;
            if (!$toolId) {
                $content = 'Error: Invalid tool name format';
                
                // Save tool response to chat history
                if ($channelId !== null && $sender !== null) {
                    $this->saveChatHistory([
                        'channel_id' => $channelId,
                        'bot_id' => $bot->id,
                        'sender' => $sender,
                        'message' => $content,
                        'role' => 'tool',
                        'message_type' => 'tool_response',
                        'tool_call_id' => $toolCallId,
                        'metadata' => [
                            'tool_name' => $toolName,
                            'timestamp' => now()->toISOString(),
                            'error' => true
                        ]
                    ]);
                }
                
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => $content,
                ];
                continue;
            }
            
            // Handle both string and array arguments
            $arguments = $toolCall['function']['arguments'];
            if (is_string($arguments)) {
                $parameters = json_decode($arguments, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $content = 'Error: Invalid JSON in tool arguments';
                    
                    // Save tool response to chat history
                    if ($channelId !== null && $sender !== null) {
                        $this->saveChatHistory([
                            'channel_id' => $channelId,
                            'bot_id' => $bot->id,
                            'sender' => $sender,
                            'message' => $content,
                            'role' => 'tool',
                            'message_type' => 'tool_response',
                            'tool_call_id' => $toolCallId,
                            'metadata' => [
                                'tool_name' => $toolName,
                                'timestamp' => now()->toISOString(),
                                'error' => true
                            ]
                        ]);
                    }
                    
                    $toolResponses[] = [
                        'tool_call_id' => $toolCallId,
                        'role' => 'tool',
                        'name' => $toolName,
                        'content' => $content,
                    ];
                    continue;
                }
            } else {
                $parameters = $arguments;
            }
            
            // Find the tool by name
            $tool = Tool::where('id', $toolId)
                ->where('team_id', $bot->team_id)
                ->where('is_active', true)
                ->first();
                
            if (!$tool) {
                $content = 'Error: Tool not found or inactive';
                
                // Save tool response to chat history
                if ($channelId !== null && $sender !== null) {
                    $this->saveChatHistory([
                        'channel_id' => $channelId,
                        'bot_id' => $bot->id,
                        'sender' => $sender,
                        'message' => $content,
                        'role' => 'tool',
                        'message_type' => 'tool_response',
                        'tool_call_id' => $toolCallId,
                        'metadata' => [
                            'tool_name' => $toolName,
                            'tool_id' => $toolId,
                            'timestamp' => now()->toISOString(),
                            'error' => true
                        ]
                    ]);
                }
                
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => $content,
                ];
                continue;
            }
            
            try {
                $execution = $this->toolService->executeTool($tool, $parameters);
                
                // Handle different execution statuses
                if ($execution->status === 'completed') {
                    $content = is_array($execution->output) ? json_encode($execution->output) : (string) $execution->output;
                } elseif ($execution->status === 'failed') {
                    $content = 'Error: ' . ($execution->error ?? 'Tool execution failed');
                } else {
                    $content = 'Error: Tool execution in unexpected state: ' . $execution->status;
                }
                
                // Save tool response to chat history
                $this->saveChatHistory([
                    'channel_id' => $channelId,
                    'bot_id' => $bot->id,
                    'sender' => $sender,
                    'message' => $content,
                    'role' => 'tool',
                    'message_type' => 'tool_response',
                    'tool_call_id' => $toolCallId,
                    'metadata' => [
                        'tool_name' => $toolName,
                        'tool_id' => $tool->id,
                        'execution_status' => $execution->status,
                        'timestamp' => now()->toISOString(),
                        'error' => $execution->status !== 'completed'
                    ]
                ]);
                
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => $content,
                ];
            } catch (\Exception $e) {
                Log::error('Tool execution error', [
                    'tool_name' => $toolName,
                    'tool_id' => $toolCallId,
                    'parameters' => $parameters,
                    'error' => $e->getMessage(),
                ]);
                
                $content = 'Error: ' . $e->getMessage();
                
                // Save tool response to chat history
                if ($channelId !== null && $sender !== null) {
                    $this->saveChatHistory([
                        'channel_id' => $channelId,
                        'bot_id' => $bot->id,
                        'sender' => $sender,
                        'message' => $content,
                        'role' => 'tool',
                        'message_type' => 'tool_response',
                        'tool_call_id' => $toolCallId,
                        'metadata' => [
                            'tool_name' => $toolName,
                            'tool_id' => $tool->id ?? null,
                            'timestamp' => now()->toISOString(),
                            'error' => true,
                            'exception' => $e->getMessage()
                        ]
                    ]);
                }
                
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => $content,
                ];
            }
        }
        
        return $toolResponses;
    }

    /**
     * Sanitize function name to comply with Gemini API requirements:
     * - Must start with a letter or underscore
     * - Must be alphanumeric (a-z, A-Z, 0-9), underscores (_), dots (.) or dashes (-)
     * - Maximum length of 64 characters
     *
     * @param int $id Tool ID to append to function name
     * @param string $name Original function name
     * @return string Sanitized function name
     */
    private function sanitizeFunctionName(int $id, string $name): string
    {
        // Replace spaces with underscores
        $sanitized = str_replace(' ', '_', $name);
        
        // Remove any characters that are not alphanumeric, underscores, dots, or dashes
        $sanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '', $sanitized);
        
        // Limit to 64 characters
        if (strlen($sanitized) > 64) {
            $sanitized = substr($sanitized, 0, 64);
        }
        
        // Fallback if name becomes empty
        if (empty($sanitized)) {
            $sanitized = 'function_' . uniqid();
        }

        // Ensure it starts with a letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $sanitized)) {
            $sanitized = '_' . $sanitized;
        }

        // Add id to the function name
        $sanitized .= "_" . $id;

        return $sanitized;
    }

    /**
     * Get chat history for a specific channel, sender, and bot.
     *
     * @param int $botId Bot ID
     * @param int|null $channelId Channel ID (nullable)
     * @param string $sender Sender identifier
     * @param int $limit Maximum number of history items to retrieve
     * @return Collection Collection of chat history items
     */
    protected function getChatHistory(int $botId, ?int $channelId, string $sender, int $limit = 5): Collection
    {
        $query = ChatHistory::where('bot_id', $botId)
            ->where('sender', $sender)
            ->latest()
            ->take($limit);
            
        if ($channelId !== null) {
            $query->where('channel_id', $channelId);
        } else {
            $query->whereNull('channel_id');
        }
        
        return $query->get()->reverse()->values();
    }

    /**
     * Record token usage and calculate costs.
     *
     * @param Bot $bot Bot instance with team_id property
     * @param ChatServiceInterface|EmbeddingServiceInterface $service AI service instance
     * @param array|null $tokenUsage Token usage data with input_tokens and output_tokens
     * @param float $responseTime Response time in seconds
     * @param string $type Usage type ('chat' or 'embedding')
     * @return void
     */
    protected function recordTokenUsage(Bot $bot, ChatServiceInterface|EmbeddingServiceInterface $service, ?array $tokenUsage, float $responseTime, string $type = 'chat'): void
    {
        if (!$tokenUsage || !isset($tokenUsage['input_tokens'], $tokenUsage['output_tokens'])) {
            return;
        }

        // Determine provider and model
        $provider = $service->getProvider();
        $model = $type === 'chat' ? $service->getModel() : $service->getEmbeddingModel();
        
        // Calculate tokens per second
        $totalTokens = $tokenUsage['output_tokens'];
        $tokensPerSecond = $responseTime > 0 && $totalTokens > 0 ? round($totalTokens / $responseTime, 2) : null;
        
        // Calculate cost
        $credits = $this->tokenPricingService->calculateCost(
            $provider,
            $model,
            $tokenUsage['input_tokens'],
            $tokenUsage['output_tokens']
        );
        
        // Store token usage
        TokenUsage::create([
            'team_id' => $bot->team_id,
            'bot_id' => $bot->id,
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => $tokenUsage['input_tokens'],
            'output_tokens' => $tokenUsage['output_tokens'],
            'tokens_per_second' => $tokensPerSecond,
            'credits' => $credits,
        ]);
        
        // Deduct credits from team balance
        if ($bot->team && $bot->team->balance) {
            $bot->team->balance->decrement('amount', $credits);
        }
    }

    /**
     * Save chat history entry.
     *
     * @param array $data Chat history data to save
     * @return ChatHistory Created chat history instance
     */
    protected function saveChatHistory(array $data): ChatHistory
    {
        return ChatHistory::create($data);
    }
}
