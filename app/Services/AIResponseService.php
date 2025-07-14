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
     * @param  string                                   $format      Output format: 'whatsapp' or 'html' (default: 'whatsapp')
     * @return string|bool  String berisi jawaban terformat, atau false kalau gagal
     */
    public function generateResponse($bot, $message, $chatHistory, $media = null, $messageType = 'text', $format = 'whatsapp')
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

            // Format response based on the specified format
            if ($format === 'html') {
                return $this->convertMarkdownToHtml($response);
            } else {
                return $this->convertMarkdownToWhatsApp($response);
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
}
