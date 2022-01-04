<?php declare(strict_types=1);

namespace SyncIt\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EnvParametersCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\EnvParametersCommand
 */
class EnvParametersCommand extends BaseCommand
{

    protected function configure(): void
    {
        $this
            ->setName('params')
            ->setDescription('Display all available environment substitutions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = $this
            ->getConfig()
            ->getParameters()
            ->map(function ($value, $key) {
                return [$key, $value];
            })
            ->values()
            ->toArray()
        ;

        $table = new Table($output);
        $table
            ->setHeaderTitle('Sync-It -- Detected Env Variables')
            ->setHeaders(['Parameter', 'Current Value'])
            ->setRows($params)
        ;

        $table->render();

        return 0;
    }
}
