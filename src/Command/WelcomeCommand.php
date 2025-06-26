<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WelcomeCommand extends Command
{
    // The name of the command (the part after "bin/console")
    protected static $defaultName = 'welcome';
    
    // The command description shown when running "php bin/console list"
    protected static $defaultDescription = 'Displays welcome message and tool overview';

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Welcome to DNS Taxi - Joe');
        
        $io->text([
            'Joe is a utility tool to build and manage DNS zone files on remote DNS servers.',
            'It provides commands for creating, listing, and deploying DNS zone files.',
            '',
            'Available commands:',
        ]);
        
        $io->listing([
            'zone:create - Creates a new DNS zone file',
            'zone:list - Lists DNS zone files on remote servers',
            'zone:deploy - Deploys DNS zone files to remote servers',
            'app:update - Checks for updates and updates the application',
        ]);
        
        $io->section('Getting Started');
        
        $io->text([
            'To create a new DNS zone file:',
            '  joe zone:create example.com --output=example.com.zone',
            '',
            'To list DNS zone files on a remote server:',
            '  joe zone:list --server=dns1.example.com --user=admin',
            '',
            'To deploy a DNS zone file to a remote server:',
            '  joe zone:deploy example.com.zone --server=dns1.example.com --user=admin --reload',
            '',
            'For more information on a specific command, use:',
            '  joe help <command>',
        ]);
        
        $io->newLine();
        $io->success('Joe is ready to drive your DNS zones!');
        
        return Command::SUCCESS;
    }
}