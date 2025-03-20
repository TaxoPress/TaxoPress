<?php

namespace Publishpress\PpToolkit\Utils;

class PoFileProcessor implements PoFileProcessorInterface
{
    public function extractTermsFromFile(string $poFile): array
    {
        $content = $this->getFileContent($poFile);

        preg_match_all('/^msgid "(.+)"$/m', $content, $matches);

        $filteredMessages = array_filter($matches[1], function ($msg) {
            return !empty($msg);
        });

        sort($filteredMessages);

        return $filteredMessages;
    }

    public function extractTermsAndTranslationsFromFile(string $poFile): array
    {
        $content = $this->getFileContent($poFile);

        // Deal with the different formats of msgstr lines, which can be:
        // - msgstr "translation"
        // or
        // - msgstr ""\n"translation"
        preg_match_all('/^msgid "(?<id>.+)"\n(msgstr "(?<str_direct>.+)"|msgstr ""\n"(?<str_secondline>.*)|msgstr "(?<str_empty>.*)")/m', $content, $matches);

        foreach ($matches['id'] as $index => $id) {
            $translation = '';

            if (isset($matches['str_direct'][$index]) && !empty($matches['str_direct'][$index])) {
                $translation = $matches['str_direct'][$index];
            } elseif (isset($matches['str_secondline'][$index]) && !empty($matches['str_secondline'][$index])) {
                $translation = $matches['str_secondline'][$index];
            } elseif (isset($matches['str_empty'][$index]) && !empty($matches['str_empty'][$index])) {
                $translation = $matches['str_empty'][$index];
            }

            $translations[$id] = $translation;
        }

        return $translations;
    }

    public function getNewTerms(array $oldMessages, array $newMessages): array
    {
        return array_diff($newMessages, $oldMessages);
    }

    public function getRemovedTerms(array $oldMessages, array $newMessages): array
    {
        return array_diff($oldMessages, $newMessages);
    }

    private function getFileContent(string $poFile): string
    {
        $isRemoteFile = filter_var($poFile, FILTER_VALIDATE_URL);

        if ($isRemoteFile) {
            // Handle remote file using cURL
            $ch = curl_init($poFile);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($content === false || $httpCode !== 200) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \Exception("Failed to download remote file '{$poFile}'. Error: {$error}");
            }

            curl_close($ch);
        } else {
            if (!file_exists($poFile)) {
                throw new \Exception("Local file '{$poFile}' not found");
            }
            $content = file_get_contents($poFile);
        }

        return $content;
    }
}
