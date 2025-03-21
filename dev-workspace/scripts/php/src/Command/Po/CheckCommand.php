<?php

namespace Publishpress\PpToolkit\Command\Po;

use Publishpress\PpToolkit\Utils\ConsoleMessageFormatterInterface;
use Publishpress\PpToolkit\Utils\PoFileProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var PoFileProcessorInterface
     */
    private $poFileProcessor;

    /**
     * @var ConsoleMessageFormatterInterface
     */
    private $consoleMessageFormatter;

    public function setDependencies(
        PoFileProcessorInterface $poFileProcessor,
        ConsoleMessageFormatterInterface $consoleMessageFormatter
    ): self {
        $this->poFileProcessor = $poFileProcessor;
        $this->consoleMessageFormatter = $consoleMessageFormatter;

        return $this;
    }

    protected function configure(): void
    {
        $this->setName('po:check')
            ->setHelp('This command allows you to check a PO file for changes comparing it to a POT file.')
            ->setDescription('Check a PO file for changes comparing it to a POT file.')
            ->setHidden(false)
            ->addArgument('po', InputArgument::REQUIRED, 'The PO file to check')
            ->addArgument('pot', InputArgument::REQUIRED, 'The POT file to compare against')
            ->addOption('markdown', 'm', InputOption::VALUE_NONE, 'Output in markdown format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consoleMessageFormatter->setDependencies($input, $output);

        $this->consoleMessageFormatter->writeDebugLine('Checking the PO file...');

        $this->output = $output;
        $this->input = $input;

        $poMessages = $this->poFileProcessor->extractTermsFromFile($input->getArgument('po'));
        $potMessages = $this->poFileProcessor->extractTermsFromFile($input->getArgument('pot'));

        $poTranslatedMessages = $this->poFileProcessor->extractTermsAndTranslationsFromFile($input->getArgument('po'));

        // Empty translated messages in the PO file
        $emptyTranslatedMessages = array_filter($poTranslatedMessages, function ($translation) {
            return empty($translation);
        });

        if (!empty($emptyTranslatedMessages)) {
            $this->consoleMessageFormatter->writeHeader('Empty translations in PO file');
            $index = 0;
            foreach ($emptyTranslatedMessages as $term => $translation) {
                $this->consoleMessageFormatter->writeTerm($term, $index++);
            }
        }

        // Terms from POT that are not present in PO
        $missingTranslations = array_diff($potMessages, $poMessages);
        if (!empty($missingTranslations)) {
            $this->consoleMessageFormatter->writeHeader('Terms missing from PO file');
            $index = 0;
            foreach ($missingTranslations as $term) {
                $this->consoleMessageFormatter->writeTerm($term, $index++);
            }
        }

        // Unused terms in the PO file (terms found in the PO file not present in the POT file)
        $unusedTerms = array_diff($poMessages, $potMessages);
        if (!empty($unusedTerms)) {
            $this->consoleMessageFormatter->writeHeader('Additional terms in PO file');
            $index = 0;
            foreach ($unusedTerms as $term) {
                $this->consoleMessageFormatter->writeTerm($term, $index++);
            }
        }

        $this->outputStatistics(
            $poMessages,
            $potMessages,
            $emptyTranslatedMessages,
            $missingTranslations,
            $unusedTerms
        );

        return Command::SUCCESS;
    }

    private function outputStatistics(
        array $poMessages,
        array $potMessages,
        array $emptyTranslatedMessages,
        array $missingTranslations,
        array $unusedTerms
    ): void {
        $this->consoleMessageFormatter->writeHeader('Statistics');

        $rows = [
            ['Terms in PO', count($poMessages)],
            ['Terms in POT', count($potMessages)],
            ['Empty translations in PO file', count($emptyTranslatedMessages)],
            ['Terms missing from PO file', count($missingTranslations)],
            ['Additional terms in PO file', count($unusedTerms)]
        ];

        $headers = ['Metric', 'Count'];
        $this->consoleMessageFormatter->writeTable($headers, $rows);
    }
}
