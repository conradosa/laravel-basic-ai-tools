<?php

namespace Conrado\LaravelBasicToolsForAI;

use Exception;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class LanguageTools
{
    /**
     * Determine the language of the given text using OpenAI's gpt-4o-mini model.
     *
     * This method sends the text to OpenAI's Chat API and processes the response
     * to extract and return the detected language as a single word. If an error
     * occurs during the process or the response structure is invalid, it returns
     * an empty string.
     *
     * @param string $text The input text whose language needs to be determined.
     * @param string $default The default target output language.
     *
     * @return string The detected language as a single word, or an empty string in case of an error.
     */
    public function getLanguage(string $text, string $default = 'Portuguese'): string
    {
        try {
            $answer = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You will receive a Text. Please respond with just one word, the language of the Text provided. If you are unsure reply with: "'.$default.'".'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Text: \"{$text}\""
                    ]
                ],
                'temperature' => 0,
                'max_tokens' => 3,
            ]);

            if (!isset($answer->choices[0]->message->content)) {
                throw new Exception("Unexpected response structure from OpenAI API");
            }

            return trim($answer->choices[0]->message->content);

        } catch (Exception $e) {
            Log::error('Laravel Basic Tools for AI - ERROR - >getLanguage< : ' . $e->getMessage());
            return "";
        }
    }

    /**
     * Generates a string of keywords based on the given input text.
     * The generated keywords are language-specific, depending on the default language provided.
     *
     * @param  string  $text  The input text from which to generate keywords.
     * @param  string  $default  The default language in which the keywords should be generated. Defaults to 'Brazilian Portuguese'.
     * @return string A comma-separated string of keywords extracted from the input text.
     */
    private function generateKeywords(string $text, string $default = 'Brazilian Portuguese'): string
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that generates keywords from the provided Text.'],
                ['role' => 'system', 'content' => 'Answer strictly with a string containing words separated by commas. No extra characters or explanations.'],
                ['role' => 'system', 'content' => 'Answer strictly in '.$default.'.'],
                ['role' => 'user', 'content' => "Generate up to ten keywords for this text:\n\n".$text],
            ],
            'temperature' => 0.5,
            'max_tokens' => 60
        ]);

        $keywords = $response->choices[0]->message->content ?? '';
        $keywords = trim($keywords);
        $keywords = preg_replace('/[^a-zA-ZÀ-ÿ, ]/', '', $keywords);

        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }

        return $keywords;
    }

    /**
     * Summarizes the given input text into a concise format.
     *
     * @param  string  $text  The input text to be summarized.
     * @return string A concise summary derived from the input text.
     */
    private function summarizeText($text): string
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an assistant that summarizes text.'],
                ['role' => 'user', 'content' => "Summarize this text as concisely as possible:\n\n".$text],
            ],
            'temperature' => 0.5,
            'max_tokens' => 250
        ]);
        return trim($response->choices[0]->message->content);
    }

}