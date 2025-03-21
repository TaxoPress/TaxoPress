<?php

namespace Publishpress\PpToolkit\Utils;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleMessageFormatter implements ConsoleMessageFormatterInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    public function setDependencies(InputInterface $input, OutputInterface $output): ConsoleMessageFormatterInterface
    {
        $this->input = $input;
        $this->output = $output;

        return $this;
    }

    public function writeHeader(string $header): void
    {
        if ($this->input->getOption('markdown')) {
            $this->output->writeln("\n### {$header}\n");
        } else {
            $this->output->writeln("\n<fg=green>{$header}</>\n");
        }
    }

    public function writeTerm(string $term, int $index = -1): void
    {
        if ($index !== -1) {
            $term = "{$index}. {$term}";
        }

        $this->output->writeln($term);
    }

    public function writeDebugLine(string $line): void
    {
        if ($this->input->getOption('verbose')) {
            $this->output->writeln($line);
        }
    }

    public function writeTable(array $headers, array $rows): void
    {
        if ($this->input->getOption('markdown')) {
            $headersString = implode(' | ', $headers);
            $this->output->writeln('| ' . $headersString . ' |');
            $headersString = array_fill(0, count($headers), '----');
            $this->output->writeln('| ' . implode(' | ', $headersString) . ' |');
            foreach ($rows as $row) {
                $rowString = implode(' | ', $row);
                $this->output->writeln('| ' . $rowString . ' |');
            }
        } else {
            $table = new \Symfony\Component\Console\Helper\Table($this->output);
            $table->setHeaders($headers)
                  ->setRows($rows)
                  ->render();
        }
    }

    public function writeSuccessLine(string $line): void
    {
        $this->output->writeln('<bg=green>' . $line . '</>');
    }

    public function writeErrorLine(string $line): void
    {
        $this->output->writeln('<bg=red>' . $line . '</>');
    }
}
