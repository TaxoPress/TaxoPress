<?php

namespace Publishpress\PpToolkit\Command\Pot;

use Publishpress\PpToolkit\Utils\ConsoleMessageFormatterInterface;
use Publishpress\PpToolkit\Utils\PoFileProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompareCommand extends Command
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
        $this->setName('pot:diff')
            ->setHelp('This command allows you to compare two PO/POT files.')
            ->setDescription('Compare two PO/POT files.')
            ->setHidden(false)
            ->addArgument('old', InputArgument::REQUIRED, 'The old PO/POT file')
            ->addArgument('new', InputArgument::REQUIRED, 'The new PO/POT file')
            ->addOption('markdown', 'm', InputOption::VALUE_NONE, 'Output in markdown format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consoleMessageFormatter->setDependencies($input, $output);

        $this->consoleMessageFormatter->writeDebugLine('Comparing PO/POT files...');

        $this->output = $output;
        $this->input = $input;

        $oldArgument = $input->getArgument('old');
        $newArgument = $input->getArgument('new');

        $oldMessages = $this->poFileProcessor->extractTermsFromFile($oldArgument);
        $newMessages = $this->poFileProcessor->extractTermsFromFile($newArgument);

        if (empty($oldMessages) || empty($newMessages)) {
            $this->consoleMessageFormatter->writeErrorLine('No messages found in the PO/POT files');
            return Command::FAILURE;
        }

        $newTerms = $this->poFileProcessor->getNewTerms($oldMessages, $newMessages);
        $removedTerms = $this->poFileProcessor->getRemovedTerms($oldMessages, $newMessages);

        if (empty($newTerms) && empty($removedTerms)) {
            $this->consoleMessageFormatter->writeSuccessLine('No new or removed terms found in the PO/POT files');
            return Command::SUCCESS;
        }

        $this->output->writeln('Comparing ' . $oldArgument . ' and ' . $newArgument);

        if (!empty($newTerms)) {
            $this->consoleMessageFormatter->writeHeader('Terms Added');
            $this->output->writeln('Terms added to the POT file:');
            $this->outputTerms($newTerms);
        }

        if (!empty($removedTerms)) {
            $this->consoleMessageFormatter->writeHeader('Terms Removed');
            $this->output->writeln('Terms removed from the POT file:');
            $this->outputTerms($removedTerms);
        }

        $this->outputStatistics($oldMessages, $newMessages, $newTerms, $removedTerms);

        return Command::SUCCESS;
    }

    private function outputTerms(array $terms): void
    {
        $terms = array_map(function ($term) {
            return [$term];
        }, $terms);

        if ($this->input->getOption('markdown')) {
            $this->consoleMessageFormatter->writeTable(['Term'], $terms);
        } else {
            foreach ($terms as $term) {
                $this->consoleMessageFormatter->writeTerm($term[0]);
            }
        }
    }

    private function outputStatistics(
        array $oldMessages,
        array $newMessages,
        array $newTerms,
        array $removedTerms
    ): void {
        $this->consoleMessageFormatter->writeHeader('Statistics');

        $rows = [
            ['Old terms total', count($oldMessages)],
            ['New terms total', count($newMessages)],
            ['New terms added', count($newTerms)],
            ['Terms removed', count($removedTerms)]
        ];

        $headers = ['Metric', 'Count'];
        $this->consoleMessageFormatter->writeTable($headers, $rows);
    }
}
