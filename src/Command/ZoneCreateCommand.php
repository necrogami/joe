<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class ZoneCreateCommand extends Command
{
    // The name of the command (the part after "bin/console")
    protected static $defaultName = 'zone:create';
    
    // The command description shown when running "php bin/console list"
    protected static $defaultDescription = 'Creates a new DNS zone file';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain name for the zone file')
            ->addOption('nameserver', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Nameserver(s) for the domain', [])
            ->addOption('admin-email', 'e', InputOption::VALUE_REQUIRED, 'Admin email for the zone file', 'admin@example.com')
            ->addOption('ttl', 't', InputOption::VALUE_REQUIRED, 'Default TTL for the zone file', '3600')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domain = $input->getArgument('domain');
        $nameservers = $input->getOption('nameserver');
        $adminEmail = $input->getOption('admin-email');
        $ttl = $input->getOption('ttl');
        $outputPath = $input->getOption('output');

        $io->title("Creating DNS zone file for $domain");

        // Validate domain
        if (!$this->isValidDomain($domain)) {
            $io->error("Invalid domain name: $domain");
            return Command::FAILURE;
        }

        // Validate nameservers
        if (empty($nameservers)) {
            $nameservers = ["ns1.$domain", "ns2.$domain"];
            $io->note("No nameservers provided, using default: " . implode(', ', $nameservers));
        }

        // Generate zone file content
        $zoneContent = $this->generateZoneFile($domain, $nameservers, $adminEmail, $ttl);

        // Output or save the zone file
        if ($outputPath) {
            file_put_contents($outputPath, $zoneContent);
            $io->success("Zone file for $domain created at $outputPath");
        } else {
            $io->section("Zone File Content");
            $io->writeln($zoneContent);
            $io->note("To save this zone file, use the --output option.");
        }

        return Command::SUCCESS;
    }

    /**
     * Validate a domain name
     */
    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
    }

    /**
     * Generate a basic zone file
     */
    private function generateZoneFile(string $domain, array $nameservers, string $adminEmail, string $ttl): string
    {
        // Convert email to zone file format (replace @ with .)
        $zoneEmail = str_replace('@', '.', $adminEmail);

        // Start with SOA record
        $content = "; Zone file for $domain\n";
        $content .= "\$TTL $ttl\n";
        $content .= "@       IN      SOA     {$nameservers[0]}. $zoneEmail. (\n";
        $content .= "                        " . date('Ymd') . "01 ; Serial\n";
        $content .= "                        3600       ; Refresh\n";
        $content .= "                        1800       ; Retry\n";
        $content .= "                        604800     ; Expire\n";
        $content .= "                        86400 )    ; Minimum TTL\n\n";

        // Add NS records
        foreach ($nameservers as $ns) {
            $content .= "@       IN      NS      $ns.\n";
        }
        $content .= "\n";

        // Add A record for the domain
        $content .= "@       IN      A       127.0.0.1 ; Replace with actual IP\n";
        
        // Add CNAME for www
        $content .= "www     IN      CNAME   @\n";

        // Add MX record
        $content .= "@       IN      MX      10 mail.$domain.\n";
        $content .= "mail    IN      A       127.0.0.1 ; Replace with actual mail server IP\n";

        return $content;
    }
}