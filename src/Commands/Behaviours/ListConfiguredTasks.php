<?php

declare(strict_types=1);

namespace SyncIt\Commands\Behaviours;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Models\Config;
use SyncIt\Models\SyncTask;

/**
 * Trait ListConfiguredTasks
 *
 * @package    SyncIt\Commands\Behaviours
 * @subpackage SyncIt\Commands\Behaviours\ListConfiguredTasks
 *
 * @method Config getConfig()
 */
trait ListConfiguredTasks
{

    protected function buildTaskTableHelper(OutputInterface $output)
    {
        $tasks = $this->getConfig()->getTasks();

        $table = new Table($output);
        $table
            ->setHeaderTitle('Mutagen Sync-It Configured Tasks')
            ->setHeaders(['Label', 'Source', 'Target'])
        ;

        $tasks->each(function (SyncTask $task) use ($table) {
            $table->addRow([$task->getLabel(), $task->getSource(), $task->getTarget()]);
        });

        return $table;
    }
}
