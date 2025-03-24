<?php

namespace Publishpress\PpToolkit\Utils;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface ConsoleMessageFormatterInterface
{
    public function setDependencies(InputInterface $input, OutputInterface $output): ConsoleMessageFormatterInterface;

    public function writeHeader(string $header): void;

    public function writeTerm(string $term): void;

    public function writeDebugLine(string $line): void;

    public function writeTable(array $headers, array $rows): void;

    public function writeSuccessLine(string $line): void;

    public function writeErrorLine(string $line): void;
}
