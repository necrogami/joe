<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class HelloWorldCommand extends Command
{
    // The name of the command (the part after "bin/console")
    protected static $defaultName = 'app:hello-world';
    
    // The command description shown when running "php bin/console list"
    protected static $defaultDescription = 'Outputs a hello world message';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?', 'World')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Uppercase the message');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $message = sprintf('Hello, %s!', $name);
        
        if ($input->getOption('uppercase')) {
            $message = strtoupper($message);
        }
        
        $output->writeln($message);

        // Return a success status code
        return Command::SUCCESS;
    }
}