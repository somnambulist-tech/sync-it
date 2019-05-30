<?php

declare(strict_types=1);

namespace SyncIt\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Models\SyncTask;

/**
 * Class StatusCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\StatusCommand
 */
class StatusCommand extends BaseCommand
{

    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Display the current task status information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getMutagen()->assertDaemonIsRunning();

        $tasks = $this->getConfig()->getTasks();

        $this->getMutagen()->getSessions()->map($tasks);

        $table = new Table($output);
        $table
            ->setHeaderTitle('Mutagen Sync-It Active Tasks')
            ->setHeaders(['Label', 'Session ID', 'Conn State', 'Sync Status'])
        ;

        $tasks->each(function (SyncTask $task) use ($table) {
            if ($task->isRunning()) {
                $table->addRow([
                    $task->getLabel(),
                    $task->getSession()->getId(),
                    $task->getSession()->getConnectionState(),
                    $task->getSession()->getStatus() ?? '--',
                ]);
            } else {
                $table->addRow([
                    $task->getLabel(),
                    '--',
                    '--',
                    '<comment>stopped</comment>',
                ]);
            }
        });

        $table->addRow(new TableSeparator());
        $table->addRow([
            new TableCell(
                'Run: "<comment>mutagen list</comment>" for raw output; or <comment>view <label></comment> for more details',
                ['colspan' => 4]
            )
        ]);

        $table->render();

        return 0;
    }
}
