<?php

namespace App\Services;

use App\Services\AIServiceFactory;
use App\Services\Contracts\EmbeddingServiceInterface;
use App\Models\ChatMedia;
use App\Models\Tool;
use Illuminate\Support\Facades\Log;

class AIResponseService
{
    protected ToolService $toolService;
    protected bool $toolCallingEnabled = true;

    public function __construct(ToolService $toolService)
    {
        $this->toolService = $toolService;
    }
    /**
     * Generate AI response for a bot with message and chat history.
     *
     * @param  object                                   $bot         Instance model bot (memiliki properti "prompt")
     * @param  string                                   $message     Pesan terbaru dari pengguna
     * @param  \Illuminate\Support\Collection           $chatHistory Koleksi objek riwayat obrolan
     * @param  \App\Models\ChatMedia|null              $media       Media data (optional)
     * @param  string                                   $format      Output format: 'whatsapp' or 'html' (default: 'whatsapp')
     * @return string|bool  String berisi jawaban terformat, atau false kalau gagal
     */
    public function generateResponse($bot, $message, $chatHistory, ?ChatMedia $media, $format = 'whatsapp')
    {
        try {
            // Get separately configured services
            $chatService = AIServiceFactory::createChatService();
            $embeddingService = AIServiceFactory::createEmbeddingService();
            
            // Search for relevant knowledge using embedding service
            $relevantKnowledge = $this->searchSimilarKnowledge($embeddingService, $message, $bot, 3);
            
            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($bot, $relevantKnowledge);
            
            // Build messages array
            $messages = $this->buildMessagesArray($systemPrompt, $chatHistory, $message);
            
            // Get available tools for the bot if tool calling is enabled
            $tools = $this->toolCallingEnabled 
                ? $this->getAvailableToolsForBot($bot)
                : [];
            
            // Generate response using chat service
            $response = $chatService->generateResponse(
                $messages,
                null, // model parameter
                $media ? $media->getData() : null,
                $media ? $media->mime_type : null,
                $tools
            );

            // Handle tool calls if present in the response
            if (is_array($response) && isset($response['tool_calls']) && !empty($response['tool_calls'])) {
                $toolResponses = $this->handleToolCalls($response['tool_calls'], $messages, $bot);

                // Add assistant message with tool calls
                $messages[] = [
                    'role' => 'assistant', 
                    'content' => $response['content'] ?? null,
                    'tool_calls' => $response['tool_calls']
                ];
                
                // Add tool responses
                $messages = array_merge($messages, $toolResponses);
                
                // Generate final response
                $finalResponse = $chatService->generateResponse($messages, null, null, null, []);
                $responseContent = is_array($finalResponse) ? ($finalResponse['content'] ?? '') : $finalResponse;
            } else {
                $responseContent = is_array($response) ? ($response['content'] ?? $response) : $response;
            }

            // Format response based on the specified format
            if ($format === 'html') {
                return $this->convertMarkdownToHtml($responseContent);
            } else {
                return $this->convertMarkdownToWhatsApp($responseContent);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate response: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build system prompt with bot prompt and relevant knowledge.
     */
    private function buildSystemPrompt($bot, $relevantKnowledge)
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
     */
    private function buildMessagesArray($systemPrompt, $chatHistory, $message)
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add chat history - handle both object and array formats
        foreach ($chatHistory as $ch) {
            if (is_object($ch)) {
                // Object format (from WhatsAppMessageController)
                $messages[] = ['role' => 'user', 'content' => $ch->message];
                $messages[] = ['role' => 'assistant', 'content' => $ch->response];
            } else {
                // Array format (from BotController)
                $messages[] = ['role' => 'user', 'content' => $ch['message']];
                $messages[] = ['role' => 'assistant', 'content' => $ch['response']];
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    /**
     * Convert markdown formatting to HTML.
     */
    public function convertMarkdownToHtml($text)
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
     */
    public function convertMarkdownToWhatsApp($text)
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

    public function searchSimilarKnowledge(EmbeddingServiceInterface $embeddingService, $query, $bot, int $limit = 3)
    {
        try {
            // Create embedding for the query
            $queryEmbedding = $embeddingService->createEmbedding($query);

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
            return $knowledgeVectors->sortByDesc('similarity')
                ->take($limit)
                ->values();

        } catch (\Exception $e) {
            Log::error('Error searching similar knowledge: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Calculate similarity between two vectors using fast C extension if available,
     * otherwise fallback to PHP implementation.
     */
    protected function calculateSimilarity($vector1, $vector2)
    {
        if (function_exists('fast_cosine_similarity')) {
            return fast_cosine_similarity($vector1, $vector2);
        }

        return $this->cosineSimilarity($vector1, $vector2);
    }

    /**
     * Calculate cosine similarity between two vectors using PHP implementation.
     */
    protected function cosineSimilarity($vector1, $vector2)
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
     */
    protected function getAvailableToolsForBot($bot): array
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
     */
    protected function handleToolCalls(array $toolCalls, array $messages, $bot): array
    {
        $toolResponses = [];
        
        foreach ($toolCalls as $toolCall) {
            $toolCallId = $toolCall['id'];
            $toolName = $toolCall['function']['name'];

            $toolId = preg_match('/_(\d+)$/', $toolName, $matches) ? $matches[1] : null;
            if (!$toolId) {
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => 'Error: Invalid tool name format',
                ];
                continue;
            }
            
            // Handle both string and array arguments
            $arguments = $toolCall['function']['arguments'];
            if (is_string($arguments)) {
                $parameters = json_decode($arguments, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $toolResponses[] = [
                        'tool_call_id' => $toolCallId,
                        'role' => 'tool',
                        'name' => $toolName,
                        'content' => 'Error: Invalid JSON in tool arguments',
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
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => 'Error: Tool not found or inactive',
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
                
                $toolResponses[] = [
                    'tool_call_id' => $toolCallId,
                    'role' => 'tool',
                    'name' => $toolName,
                    'content' => 'Error: ' . $e->getMessage(),
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
}
