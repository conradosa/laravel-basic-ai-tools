<?php

namespace Conrado\LaravelBasicToolsForAI;

use Exception;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use OpenAI\Exceptions\ErrorException;
use GuzzleHttp\Exception\RequestException;
use OpenAI\Laravel\Facades\OpenAI;

class GeneralTools
{
    /**
     * Estimate the token count based on the given text.
     *
     * @param string $text The input text to estimate the token count for.
     * @return int The estimated token count.
     */
    public function estimateTokenCount(string $text): int
    {
        $charCount = strlen($text);
        return intval($charCount / 4);
    }

    /**
     * Determines if a given question requires summarization.
     *
     * Uses the OpenAI API to analyze a user question and a content summary.
     * If the question is deemed general and could be answered using the summary, returns true.
     * Otherwise, returns false.
     *
     * @param string $question The user questions to analyze.
     * @param string $summary The summary of the content provided.
     * @return bool True if summarization is needed, otherwise false.
     */
    public function needToSummarize(string $question, string $summary): bool
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an assistant answering questions about a large content.
                      You will receive a content summary and a user question.
                      If the question is specific, meaning it asks you about a part of the content or a subject of the content, answer "No".
                      If the question is general, meaning it lacks precision, asks to create content and could be answered with the summary received, answer "Yes".
                      Answer strictly with "Yes" or "No".'
                ],
                [
                    'role' => 'user',
                    'content' => 'Summary: "' . $summary . '" Question: "' . $question . '".'
                ],
            ],
            'temperature' => 0,
            'max_tokens' => 2
        ]);
        if (str_contains(strtolower(trim($response->choices[0]->message->content)), 'yes')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Process an AI query by interfacing with the OpenAI chat API. Attempts up to five retries
     * in case of failures, logging errors and returning a fallback message when necessary.
     *
     * @param array $messages An array of message data to be sent to the OpenAI chat API.
     *
     * @return string Returns the AI-generated response as a string or a fallback error message
     *                if the process fails, after all, retries.
     */
    public function aiQuery(array $messages): string
    {
        $count = 0;
        while ($count < 5) {
            try {
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 1000,
                    'temperature' => 0.2
                ]);

                return trim($response->choices[0]->message->content);

            } catch (ErrorException|RequestException $e) {
                Log::error('OpenAI error: ' . $e->getMessage());
                $count++;
            } catch (Exception $e) {
                Log::error('Server error in Query: ' . $e->getMessage());
                return 'Algo deu errado, contate o suporte.';
            }
        }
        return 'Algo deu errado, contate o suporte.';
    }

    /**
     * Sanitizes the input string for embedding generation by cleaning and truncating it.
     *
     * This method removes control characters, collapses multiple whitespace characters into a single space,
     * and limits the string to a specified maximum length.
     *
     * @param  string  $input  The input string to be sanitized.
     * @param  int  $maxLength  The maximum allowed length of the sanitized string. Default is 500.
     * @return string The sanitized and truncated input string, ready for processing.
     */
    function sanitizeEmbeddingInput(string $input, int $maxLength = 500): string
    {
        $clean = trim($input);
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return Str::limit($clean, $maxLength, '');
    }

    /**
     * Generate an embedding for the given text using OpenAI's API.
     *
     * This method attempts to create an embedding for the input text by invoking the OpenAI API.
     * It retries the request up to five times in case of recoverable errors and logs any errors encountered.
     * If the maximum number of retries is reached, an exception is thrown.
     *
     * @param string $text The input text for which the embedding is to be generated.
     * @return string The generated embedding data.
     * @throws Exception If an unrecoverable error occurs or the maximum number of retries is exceeded.
     */
    public function generateEmbedding(string $text): string
    {
        try {
            $sanitized_query = $this->sanitizeEmbeddingInput(trim($text));
        } catch (Exception $e) {
            throw new Exception('Generate embedding error while sanitizing input: ' . $e->getMessage());
        }
        $count = 0;
        while ($count < 5) {
            try {
                $embedding = OpenAI::embeddings()->create([
                    'model' => 'text-embedding-ada-002',
                    'input' => $sanitized_query,
                ]);
                return '[' . implode(',', $embedding['data'][0]['embedding']) . ']';
            } catch (RequestException $e) {
                Log::error('OpenAI error: ' . $e->getMessage());
                $count++;
            } catch (Exception $e) {
                Log::error('Server error in generateEmbedding function: ' . $e->getMessage());
                $count++;
            }
        }
        throw new Exception('Server error in generateEmbedding function');
    }

    /**
     * Generate a unique token by attempting a set number of retries and ensuring
     * its uniqueness in the database. Handles exceptions and logs errors if any occur.
     *
     * @return JsonResponse|string Returns a JSON response with an error message if the token cannot
     * be generated or the unique token as a string.
     */
    public function generateUniqueToken(Model $model, $response = "Erro com a IA, contate o suporte."): JsonResponse|string
    {
        try {
            $maxAttempts = 10;
            $attempt = 0;
            do {
                $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    return response()->json([
                        'token' => 'error',
                        'response' => $response
                    ]);
                }
            } while ($model->where('token', $token)->exists());
            return $token;
        } catch (Exception $e) {
            Log::error('Generate Unique Token error: ' . $e->getMessage());
            return '';
        }
    }
}