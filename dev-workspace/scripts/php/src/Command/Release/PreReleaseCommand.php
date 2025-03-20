<?php

namespace Publishpress\PpToolkit\Command\Release;

use Github\Client;
use Github\AuthMethod;
use Publishpress\PpToolkit\Utils\ConsoleMessageFormatterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PreReleaseCommand extends Command
{
    protected static $defaultName = 'release:pre';

    protected static $defaultDescription = 'Creates a release branch and PR';

    private SymfonyStyle $io;

    /**
     * @var ConsoleMessageFormatterInterface
     */
    private $consoleMessageFormatter;

    public function setDependencies(
        ConsoleMessageFormatterInterface $consoleMessageFormatter
    ): self {
        $this->consoleMessageFormatter = $consoleMessageFormatter;
        return $this;
    }

    protected function configure(): void
    {
        $this->setName(self::$defaultName)
            ->setHelp(self::$defaultDescription)
            ->setDescription(self::$defaultDescription)
            ->setHidden(false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Check current branch
        $currentBranch = $this->runGitCommand(['branch', '--show-current']);

        if (!in_array($currentBranch, ['main', 'master'])) {
            if (!$this->io->confirm(
                sprintf('Warning: You\'re not on main/master branch. Current branch: %s. Continue?', $currentBranch),
                false
            )) {
                return Command::FAILURE;
            }
        }

        // Check working directory
        if ($this->runGitCommand(['status', '-s'])) {
            $this->io->error('Working directory is not clean. Please commit or stash changes first.');
            return Command::FAILURE;
        }

        // Fetch latest changes
        $this->io->section('Fetching latest changes...');
        $this->runGitCommand(['fetch', 'origin']);

        // Get version number
        $version = $this->io->ask('Enter the version number to release (x.x.x)');

        if (!$this->validateVersion($version)) {
            $this->io->error('Invalid version format. Please use x.x.x or x.x.x-beta.x');
            return Command::FAILURE;
        }

        $branchName = "release-{$version}";

        // Create or checkout branch
        if (!$this->branchExists($branchName)) {
            $this->io->section("Creating branch {$branchName}...");
            $this->runGitCommand(['checkout', '-b', $branchName]);
        } elseif ($currentBranch !== $branchName) {
            $this->runGitCommand(['checkout', $branchName]);
        }

        // Push branch
        try {
            $this->runGitCommand(['push', '-u', 'origin', $branchName]);
        } catch (\Exception $e) {
            $this->io->note("Branch {$branchName} already pushed to remote.");
        }

        // Create PR
        $checklist = $this->createChecklist($version);
        $this->createPullRequest($version, $checklist, $branchName);

        return Command::SUCCESS;
    }

    private function validateVersion(string $version): bool
    {
        return (bool) preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?$/', $version);
    }

    private function runGitCommand(array $command): string
    {
        $cmd = 'git ' . implode(' ', array_map('escapeshellarg', $command));
        exec($cmd . ' 2>&1', $output, $returnCode);

        $output = implode("\n", $output);

        if ($returnCode !== 0 && !in_array('status', $command)) {
            throw new \RuntimeException("Command failed: " . $output);
        }

        return trim($output);
    }

    private function branchExists(string $branch): bool
    {
        try {
            $this->runGitCommand(['show-ref', '--verify', '--quiet', "refs/heads/{$branch}"]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createChecklist(string $version): string
    {
        $template = file_get_contents('dev-workspace/pr-template.md');
        return str_replace('$1', $version, $template);
    }

    private function createPullRequest(string $version, string $checklist, string $branchName): void
    {
        $this->io->section('Creating pull request...');

        try {
            $client = new Client();

            // Load GitHub token
            $token = $this->getGithubToken();
            if (empty($token)) {
                throw new \RuntimeException('GitHub token not found. Please set GITHUB_ACCESS_TOKEN in .env file');
            }

            $client->authenticate($token, null, AuthMethod::ACCESS_TOKEN);

            // Get repository details from git remote
            [$owner, $repo] = $this->getRepositoryInfo();

            // Create the pull request
            $pullRequest = $client->pullRequests()->create($owner, $repo, [
                'title' => "Release {$version}",
                'body'  => $checklist,
                'head'  => $branchName,
                'base'  => 'main'
            ]);

            $this->io->success(sprintf(
                'Pull request created successfully! View it here: %s',
                $pullRequest['html_url']
            ));

        } catch (\Exception $e) {
            $this->io->error('Failed to create pull request: ' . $e->getMessage());
        }
    }

    private function getGithubToken(): string
    {
        if (!file_exists('.env')) {
            return '';
        }

        $envContent = parse_ini_file('.env');
        return $envContent['GITHUB_ACCESS_TOKEN'] ?? '';
    }

    private function getRepositoryInfo(): array
    {
        // Get the remote URL
        $remoteUrl = $this->runGitCommand(['remote', 'get-url', 'origin']);

        // Parse the URL to get owner and repo
        // Handle both HTTPS and SSH URLs:
        // HTTPS: https://github.com/owner/repo.git
        // SSH: git@github.com:owner/repo.git
        if (preg_match('#^(?:https://github\.com/|git@github\.com:)([^/]+)/([^/]+?)(?:\.git)?$#', $remoteUrl, $matches)) {
            return [$matches[1], $matches[2]];
        }

        throw new \RuntimeException('Could not determine repository owner and name from git remote URL');
    }
}
