<?php

namespace App\Services;

use App\Services\AIServiceFactory;
use Illuminate\Support\Facades\Log;

class AIResponseService
{
    /**
     * Generate AI response for a bot with message and chat history.
     *
     * @param  object                                   $bot         Instance model bot (memiliki properti "prompt")
     * @param  string                                   $message     Pesan terbaru dari pengguna
     * @param  \Illuminate\Support\Collection           $chatHistory Koleksi objek riwayat obrolan
     * @param  array|null                               $media       Media data (optional)
     * @param  string                                   $messageType Message type (default: 'text')
     * @return string|bool  String berisi jawaban terformat, atau false kalau gagal
     */
    public function generateResponse($bot, $message, $chatHistory, $media = null, $messageType = 'text')
    {
        try {
            // Get separately configured services
            $chatService = AIServiceFactory::createChatService();
            $embeddingService = AIServiceFactory::createEmbeddingService();
            
            Log::info('Using AI services', [
                'chat_service' => get_class($chatService),
                'embedding_service' => get_class($embeddingService),
            ]);
            
            // Search for relevant knowledge using embedding service
            $relevantKnowledge = $embeddingService->searchSimilarKnowledge($message, $bot, 3);
            
            Log::info('Relevant Knowledge found:', [
                'knowledge_count' => $relevantKnowledge->count(),
                'message' => $message,
            ]);
            
            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($bot, $relevantKnowledge);
            
            // Build messages array
            $messages = $this->buildMessagesArray($systemPrompt, $chatHistory, $message);
            
            // Generate response using chat service
            $response = $chatService->generateResponse(
                $messages, 
                null, 
                $media ? $media['data'] : null, 
                $media ? $messageType : null
            );
            
            return $this->convertMarkdownToWhatsApp($response);
            
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
}
