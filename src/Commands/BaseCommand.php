<?php declare(strict_types=1);

namespace SyncIt\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Models\Config;
use SyncIt\Services\Config\ConfigLocator;
use SyncIt\Services\Config\ConfigParser;
use SyncIt\Services\Console\ConsoleHelper;
use SyncIt\Services\DockerContainerResolver;
use SyncIt\Services\Mutagen;

/**
 * Class BaseCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\BaseCommand
 */
abstract class BaseCommand extends Command
{
    private ?Config $config = null;
    private ?Mutagen $mutagen = null;
    private ?ConsoleHelper $consoleHelper = null;

    public function setupConsoleHelper(InputInterface $input, OutputInterface $output): void
    {
        $this->consoleHelper = new ConsoleHelper($input, $output);
    }

    protected function getMutagen(): Mutagen
    {
        if ($this->mutagen) {
            return $this->mutagen;
        }

        return $this->mutagen = new Mutagen();
    }

    protected function getConfig(): Config
    {
        if ($this->config) {
            return $this->config;
        }

        return $this->config =
            (new ConfigParser(new DockerContainerResolver()))
                ->parse(file_get_contents((new ConfigLocator())->locate()))
            ;
    }

    protected function tools(): ConsoleHelper
    {
        return $this->consoleHelper;
    }
}
