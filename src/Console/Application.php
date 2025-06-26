<?php

namespace App\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Command\CompleteCommand;

/**
 * Custom Application class that extends Symfony's Console Application
 * to exclude the DumpCompletionCommand when running from a PHAR.
 */
class Application extends BaseApplication
{
    /**
     * Gets the default commands that should always be available.
     *
     * This overrides the parent method to exclude DumpCompletionCommand
     * which causes issues when running from a PHAR due to DirectoryIterator
     * limitations with PHAR files.
     *
     * @return Command[]
     */
    protected function getDefaultCommands(): array
    {
        // Return only the commands that work well in a PHAR context
        return [new HelpCommand(), new ListCommand(), new CompleteCommand()];
    }
}