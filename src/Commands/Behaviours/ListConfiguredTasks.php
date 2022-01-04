<?php declare(strict_types=1);

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
    protected function buildTaskTableHelper(OutputInterface $output): Table
    {
        $tasks = $this->getConfig()->getTasks();

        $table = new Table($output);
        $table
            ->setHeaderTitle(sprintf('Sync-It -- Configured Tasks -- Mutagen (v%s)', $this->getMutagen()->getVersion()))
            ->setHeaders(['Label', 'Source', 'Target'])
            ->setColumnWidth(1, 30)
            ->setColumnWidth(2, 30)
        ;

        $tasks->each(function (SyncTask $task) use ($table) {
            $table->addRow([$task->getLabel(), $task->getSource(), $task->getTarget()]);
        });

        return $table;
    }
}
