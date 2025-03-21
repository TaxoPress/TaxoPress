<?php

namespace Publishpress\PpToolkit\Utils;

interface PoFileProcessorInterface
{
    public function extractTermsFromFile(string $poFile): array;

    public function extractTermsAndTranslationsFromFile(string $poFile): array;

    public function getNewTerms(array $oldMessages, array $newMessages): array;

    public function getRemovedTerms(array $oldMessages, array $newMessages): array;
}
