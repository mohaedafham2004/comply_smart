<?php

namespace App\Services;

use App\Models\AiChatHistory;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AIChatService
 *
 * Wraps the Google Gemini generativelanguage API.
 *
 * Configuration (in .env):
 *   GEMINI_API_KEY=your_api_key_here
 *   GEMINI_MODEL=gemini-1.5-flash          (optional, defaults below)
 *   GEMINI_TIMEOUT=30                       (optional, seconds)
 *
 * Never hardcode the API key — always read from config('services.gemini.api_key').
 */
class AIChatService
{
    private const BASE_URL        = 'https://generativelanguage.googleapis.com/v1beta/models';
    private const DEFAULT_MODEL   = 'gemini-1.5-flash';
    private const DEFAULT_TIMEOUT = 30;

    private string $apiKey;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.gemini.api_key', '');
        $this->model   = config('services.gemini.model', self::DEFAULT_MODEL);
        $this->timeout = (int) config('services.gemini.timeout', self::DEFAULT_TIMEOUT);
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Chat with Gemini, using the last 10 turns of history for context.
     * Persists both user and assistant turns to ai_chat_history.
     *
     * @throws \RuntimeException on API key not configured
     * @return array{reply: string, error: bool}
     */
    public function chat(User $user, string $businessId, string $userMessage): array
    {
        $this->assertApiKey();

        // Persist the user's turn BEFORE the API call
        AiChatHistory::create([
            'user_id'     => (string) $user->_id,
            'business_id' => $businessId,
            'role'        => AiChatHistory::ROLE_USER,
            'message'     => $userMessage,
        ]);

        // Build conversation contents from the last 10 turns (including the new message)
        $history  = AiChatHistory::where('user_id', (string) $user->_id)
            ->where('business_id', $businessId)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        $contents = $history->map(fn ($e) => [
            'role'  => $e->role === AiChatHistory::ROLE_USER ? 'user' : 'model',
            'parts' => [['text' => $e->message]],
        ])->toArray();

        // Call Gemini
        $result = $this->callGemini(':generateContent', [
            'systemInstruction' => [
                'parts' => [['text' => $this->chatSystemPrompt()]],
            ],
            'contents'         => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1024,
                'temperature'     => 0.7,
            ],
        ]);

        $assistantMessage = $result['text']
            ?? 'I apologize — I could not generate a response right now. Please try again shortly.';

        // Persist assistant turn
        AiChatHistory::create([
            'user_id'     => (string) $user->_id,
            'business_id' => $businessId,
            'role'        => AiChatHistory::ROLE_ASSISTANT,
            'message'     => $assistantMessage,
        ]);

        return [
            'reply' => $assistantMessage,
            'error' => $result['error'] ?? false,
        ];
    }

    /**
     * Generate a structured compliance checklist for a business type.
     *
     * Returns an array of items, each with:
     *   { title, category, description, priority, suggested_due_in_days }
     *
     * @return array{items: array, error: bool, error_message?: string}
     */
    public function generateChecklist(
        string $businessType,
        string $description = '',
        string $location    = 'Sri Lanka'
    ): array {
        $this->assertApiKey();

        $descriptionLine = $description
            ? "\nAdditional context: {$description}"
            : '';

        $prompt = <<<PROMPT
You are a Sri Lankan business compliance expert.
Generate a concise compliance checklist for a "{$businessType}" business in {$location}.{$descriptionLine}

Return ONLY a valid JSON array (no markdown, no explanation). Generate 5-7 key items. Each item must have exactly these fields:
- "title": short name of the compliance requirement (string)
- "category": one of: registration, tax, labor, health_safety, environment, permits, sector_specific (string)
- "description": 1 concise sentence explaining what is required and how to comply (string)
- "priority": one of: high, medium, low (string)
- "suggested_due_in_days": how many days from business start to complete this (integer, e.g. 30, 60, 90, 180)

Cover: business registration, tax, labor EPF/ETF, health & safety, and sector permits.
Order items by priority (high first), then suggested_due_in_days ascending.
PROMPT;

        $result = $this->callGemini(':generateContent', [
            'contents'         => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'maxOutputTokens'  => 4096,
                'temperature'      => 0.2, // low temp for structured output
            ],
        ]);

        if ($result['error']) {
            return ['items' => [], 'error' => true, 'error_message' => $result['text']];
        }

        $rawJson = $result['text'] ?? '[]';

        // Strip any accidental markdown fences Gemini may still wrap
        $rawJson = preg_replace('/^```(?:json)?\s*/m', '', $rawJson);
        $rawJson = preg_replace('/```\s*$/m', '', $rawJson);

        $items = json_decode(trim($rawJson), true);

        if (!is_array($items)) {
            Log::warning('AIChatService: Failed to parse checklist JSON', ['raw' => $rawJson]);
            return ['items' => [], 'error' => true, 'error_message' => 'Failed to parse Gemini response as JSON.'];
        }

        return ['items' => $items, 'error' => false];
    }

    /**
     * Summarize a regulation or document in plain language.
     *
     * Accepts either:
     *   - Raw text (pass $text directly)
     *   - A Document ID (pass $documentId; will use ocr_extracted_text)
     *
     * @return array{summary: string, error: bool, error_message?: string}
     */
    public function summarize(string $text = '', ?string $documentId = null): array
    {
        $this->assertApiKey();

        // Resolve source text
        if ($documentId) {
            $document = Document::find($documentId);
            if (!$document) {
                return ['summary' => '', 'error' => true, 'error_message' => 'Document not found.'];
            }
            if (empty($document->ocr_extracted_text)) {
                return ['summary' => '', 'error' => true, 'error_message' => 'OCR text not yet available for this document. Please wait for OCR to complete.'];
            }
            $text = $document->ocr_extracted_text;
        }

        if (empty(trim($text))) {
            return ['summary' => '', 'error' => true, 'error_message' => 'No text provided to summarize.'];
        }

        // Truncate to avoid token limits (~12 000 chars ≈ ~3 000 tokens)
        $truncated = mb_strlen($text) > 12000
            ? mb_substr($text, 0, 12000) . "\n\n[... document truncated for length ...]"
            : $text;

        $prompt = $this->summarizationPrompt($truncated);

        $result = $this->callGemini(':generateContent', [
            'contents'         => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 1024,
                'temperature'     => 0.3,
            ],
        ]);

        if ($result['error']) {
            return ['summary' => '', 'error' => true, 'error_message' => $result['text']];
        }

        return [
            'summary' => $result['text'] ?? 'Unable to summarize the document.',
            'error'   => false,
        ];
    }

    /**
     * Retrieve conversation history for a user+business pair.
     */
    public function getHistory(string $userId, string $businessId, int $limit = 50): \Illuminate\Support\Collection
    {
        return AiChatHistory::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values();
    }

    // ─── Private: Gemini HTTP client ──────────────────────────────────────────

    /**
     * Make a call to the Gemini REST API.
     * Returns ['text' => string, 'error' => bool].
     */
    private function callGemini(string $endpoint, array $body): array
    {
        $url = self::BASE_URL . '/' . $this->model . $endpoint;

        try {
            $response = Http::withoutVerifying()
                ->withQueryParameters(['key' => $this->apiKey])
                ->timeout($this->timeout)
                ->acceptJson()
                ->post($url, $body);

            if ($response->failed()) {
                $errorBody = $response->json();
                $apiMsg    = $errorBody['error']['message'] ?? ('HTTP ' . $response->status());
                Log::error('Gemini API error', [
                    'status'   => $response->status(),
                    'message'  => $apiMsg,
                    'endpoint' => $endpoint,
                ]);
                return ['text' => "Gemini API error: {$apiMsg}", 'error' => true];
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            // Handle safety blocks / empty responses
            if ($text === null) {
                $finishReason = $response->json('candidates.0.finishReason');
                if ($finishReason === 'SAFETY') {
                    return ['text' => 'My response was blocked by safety filters. Please rephrase your request.', 'error' => false];
                }
                Log::warning('Gemini returned null text', [
                    'endpoint' => $endpoint,
                    'body'     => $response->json(),
                ]);
                return ['text' => 'No response generated. Please try again.', 'error' => true];
            }

            return ['text' => $text, 'error' => false];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini connection timeout', ['message' => $e->getMessage()]);
            return [
                'text'  => 'Request timed out. The AI service may be temporarily unavailable. Please try again.',
                'error' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini unexpected error', ['message' => $e->getMessage()]);
            return [
                'text'  => 'An unexpected error occurred while contacting the AI service.',
                'error' => true,
            ];
        }
    }

    // ─── Private: Prompt Templates ────────────────────────────────────────────

    /**
     * System prompt for the compliance chatbot.
     * Sets persona, scope, and tone.
     */
    private function chatSystemPrompt(): string
    {
        return <<<PROMPT
You are ComplySmart AI, a friendly and expert compliance assistant for small businesses in Sri Lanka.

Your areas of expertise:
- Business registration (Registrar of Companies, sole trader, partnership)
- Tax obligations: VAT (threshold LKR 80M), Income Tax, Withholding Tax, Economic Service Charge
- Labour law: EPF (employer 12%, employee 8%), ETF (employer 3%), minimum wage, termination rules
- Health & safety regulations (Factories Ordinance, Food Act)
- Sector-specific permits: food handling, liquor, pharmacy, finance, tourism, construction
- Environmental compliance (CEA requirements, EIA)
- Document renewal timelines and government procedures

Guidelines:
- Always give concise, actionable, Sri Lanka-specific advice.
- Cite the relevant Act or Regulation when possible (e.g. "Companies Act No. 7 of 2007").
- If you are uncertain, say so and recommend the user contact the relevant authority.
- Do NOT give legal advice — recommend a qualified lawyer for complex issues.
- Keep responses under 400 words unless the user asks for detail.
- Be professional but approachable.
PROMPT;
    }

    /**
     * Prompt for the regulation summarizer.
     */
    private function summarizationPrompt(string $text): string
    {
        return <<<PROMPT
You are a compliance expert assistant for Sri Lankan small businesses.

Summarize the following regulation or document in plain, easy-to-understand language suitable for a non-legal business owner.

Structure your summary as follows:
1. **Overview** (2-3 sentences: what this document is about)
2. **Key Obligations** (bullet list: what businesses MUST do)
3. **Important Deadlines** (bullet list: any dates, frequencies, or time limits mentioned)
4. **Penalties for Non-Compliance** (bullet list: fines, sanctions, or consequences mentioned)
5. **Action Steps** (bullet list: 3-5 practical next steps for the business owner)

Keep each section concise. Use simple English. If a section has no relevant information, write "None mentioned."

DOCUMENT:
{$text}
PROMPT;
    }

    // ─── Private: Guards ──────────────────────────────────────────────────────

    private function assertApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException(
                'Gemini API key is not configured. Add GEMINI_API_KEY to your .env file.'
            );
        }
    }
}
