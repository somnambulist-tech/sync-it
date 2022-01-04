<?php declare(strict_types=1);

namespace SyncIt\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class MonitorCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\MonitorCommand
 */
class MonitorCommand extends BaseCommand
{

    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure(): void
    {
        $this
            ->setName('monitor')
            ->setDescription('Monitor the chosen task via mutagen monitor')
            ->addArgument('label', InputArgument::OPTIONAL, 'The task (label) to monitor')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list')) {
            $table = $this->buildTaskTableHelper($output);
            $table->render();

            return 0;
        }

        $this->getMutagen()->assertDaemonIsRunning($input, $output);

        $tasks = $this->getConfig()->getTasks();
        $label = $input->getArgument('label');

        if (!$label) {
            $label = $this->tools()->choose('Which task would you like to monitor? ', $tasks->keys()->toArray());
        }

        $this->getMutagen()->getSessions()->map($tasks);

        /** @var SyncTask $task */
        if (null === $task = $tasks->get($label)) {
            throw new InvalidArgumentException(sprintf('Task with label "%s" not found in current project', $label));
        }
        if (!$task->isRunning()) {
            throw new InvalidArgumentException(sprintf('The task "%s" is not running', $label));
        }

        $this->tools()->info('Starting monitor, use <info>Ctrl+C</info> to stop');

        /*
         * monitor provides updated output, so stream it straight back out.
         */
        passthru('mutagen sync monitor ' . $task->getSession()->getId());

        return 0;
    }
}
