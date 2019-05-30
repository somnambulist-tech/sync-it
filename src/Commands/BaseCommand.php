<?php

declare(strict_types=1);

namespace SyncIt\Commands;

use Symfony\Component\Console\Command\Command;
use SyncIt\Models\Config;
use SyncIt\Services\Config\ConfigLocator;
use SyncIt\Services\Config\ConfigParser;
use SyncIt\Services\Mutagen;

/**
 * Class BaseCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\BaseCommand
 */
abstract class BaseCommand extends Command
{

    /**
     * @var \SyncIt\Models\Config
     */
    private $config;

    /**
     * @var Mutagen
     */
    private $mutagen;

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

        return $this->config = (new ConfigParser())->parse(file_get_contents((new ConfigLocator())->locate()));
    }
}
