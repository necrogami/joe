<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
    // The name of the command (the part after "bin/console")
    protected static $defaultName = 'app:update';

    // The command description shown when running "php bin/console list"
    protected static $defaultDescription = 'Checks for updates and updates the application if a new version is available';

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Checking for updates');

        // Get current version
        $currentVersion = $this->getApplication()->getVersion();
        $io->text("Current version: $currentVersion");

        try {
            // Get latest version from GitHub
            $io->text('Checking GitHub for latest version...');
            $latestVersion = $this->getLatestVersion();
            $io->text("Latest version: $latestVersion");

            // Compare versions
            if (version_compare($currentVersion, $latestVersion, '<')) {
                $io->success("A new version is available: $latestVersion");

                if ($io->confirm('Do you want to update?', true)) {
                    $io->text('Downloading new version...');
                    $this->downloadAndUpdate($latestVersion, $io);
                    $io->success('Update completed successfully!');
                } else {
                    $io->text('Update cancelled.');
                }
            } else {
                $io->success('You are already using the latest version.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error checking for updates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get the latest version from GitHub releases
     */
    private function getLatestVersion(): string
    {
        // Get repository information from environment or use default
        $repoOwner = getenv('GITHUB_REPOSITORY_OWNER') ?: 'necrogami';
        $repoName = getenv('GITHUB_REPOSITORY') ? explode('/', getenv('GITHUB_REPOSITORY'))[1] : 'joe';

        // Allow override through environment variables
        $repoOwner = getenv('APP_GITHUB_OWNER') ?: $repoOwner;
        $repoName = getenv('APP_GITHUB_REPO') ?: $repoName;

        $url = "https://api.github.com/repos/$repoOwner/$repoName/releases/latest";
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \Exception("Failed to connect to GitHub API: $url");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse GitHub API response');
        }

        if (!isset($data['tag_name'])) {
            throw new \Exception('No version tag found in GitHub API response');
        }

        // Remove 'v' prefix if present
        return ltrim($data['tag_name'], 'v');
    }

    /**
     * Download and update the application
     */
    private function downloadAndUpdate(string $version, SymfonyStyle $io): void
    {
        // Determine if we're running from PHAR or binary
        $isPhar = strpos(__FILE__, 'phar://') === 0;
        $isBinary = PHP_SAPI === 'cli' && !$isPhar;

        // Get the current executable path
        $currentExecutable = $_SERVER['SCRIPT_FILENAME'];
        if ($isPhar) {
            // Extract the actual PHAR path from phar://path/to/file.phar/internal/path
            $parts = explode('phar://', $currentExecutable);
            $pharPath = explode('/', $parts[1])[0];
            $currentExecutable = $pharPath;
        }

        // Determine which asset to download (PHAR or binary)
        $assetName = $isPhar ? 'app.phar' : 'app';

        // Get repository information from environment or use default
        $repoOwner = getenv('GITHUB_REPOSITORY_OWNER') ?: 'necrogami';
        $repoName = getenv('GITHUB_REPOSITORY') ? explode('/', getenv('GITHUB_REPOSITORY'))[1] : 'joe';

        // Allow override through environment variables
        $repoOwner = getenv('APP_GITHUB_OWNER') ?: $repoOwner;
        $repoName = getenv('APP_GITHUB_REPO') ?: $repoName;

        // Download URL
        $downloadUrl = "https://github.com/$repoOwner/$repoName/releases/download/v$version/$assetName";

        // Temporary file
        $tempFile = sys_get_temp_dir() . '/' . uniqid('update_', true);

        // Download the file
        $io->text("Downloading from: $downloadUrl");
        $io->text("Saving to temporary file: $tempFile");

        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];

        $context = stream_context_create($options);
        $fileContent = file_get_contents($downloadUrl, false, $context);

        if ($fileContent === false) {
            throw new \Exception("Failed to download new version from $downloadUrl");
        }

        if (file_put_contents($tempFile, $fileContent) === false) {
            throw new \Exception("Failed to save downloaded file to $tempFile");
        }

        // Make the file executable if it's a binary
        if ($isBinary) {
            chmod($tempFile, 0755);
        }

        // Replace the current executable
        $io->text("Replacing current executable: $currentExecutable");

        // On Windows, we need to rename the file
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows can't replace a running executable, so we create a batch file to do it
            $batchFile = sys_get_temp_dir() . '/update_app.bat';
            $batchContent = "@echo off\r\n";
            $batchContent .= "timeout /t 1 /nobreak > nul\r\n"; // Wait a second
            $batchContent .= "copy /Y \"$tempFile\" \"$currentExecutable\"\r\n";
            $batchContent .= "del \"$tempFile\"\r\n";
            $batchContent .= "del \"%~f0\"\r\n"; // Delete this batch file

            file_put_contents($batchFile, $batchContent);

            // Execute the batch file
            pclose(popen("start /b \"\" \"$batchFile\"", 'r'));

            $io->text("Update will complete after this process exits.");
            $io->text("Please restart the application to use the new version.");
        } else {
            // On Unix systems, we can replace the file directly
            if (!rename($tempFile, $currentExecutable)) {
                throw new \Exception("Failed to replace current executable with new version");
            }

            // Make sure it's executable
            chmod($currentExecutable, 0755);
        }
    }
}
