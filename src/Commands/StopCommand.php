<?php declare(strict_types=1);

namespace SyncIt\Commands;

use Somnambulist\Components\Collection\MutableCollection as Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Commands\Behaviours\GetLabelsFromInput;
use SyncIt\Commands\Behaviours\ListConfiguredTasks;
use SyncIt\Commands\Behaviours\RunWrappedProcess;
use SyncIt\Models\SyncTask;

/**
 * Class StopCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\StopCommand
 */
class StopCommand extends BaseCommand
{
    use GetLabelsFromInput;
    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure(): void
    {
        $this
            ->setName('stop')
            ->setDescription('Stops all configured mutagen sync tasks')
            ->addArgument('label', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'The labels to stop or all', [])
            ->addOption('label', 'l', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'The task label(s) or group to stop', [])
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
            ->setHelp(<<<'HELP'
Stop a specified, or all, sync tasks as defined in the current projects' config file.

To stop all tasks run:

  <info>php %command.full_name% all</info>

To be prompted what to stop run:

  <info>php %command.full_name%</info>

To stop an individual task run:

  <info>php %command.full_name% task</info> or:
  <info>php %command.full_name% --label=task</info>

To stop multiple tasks use multiple --label calls:

  <info>php %command.full_name% task1 task2</info> or:
  <info>php %command.full_name% --label=task1 --label=task2</info>

List the available tasks:

  <info>php %command.full_name% --list</info>

If a task will not stop, turn on debugging to get the output from the
call to mutagen:

  <info>php %command.full_name% --label=task -vvv</info>

HELP
            )
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

        $tasks  = $this->getMutagen()->getSessions()->map($this->getConfig()->getTasks());
        $labels = $this->getLabelsFromInput($input, $tasks);

        if (count($labels) < 1) {
            if (!is_array($labels = $this->promptForLabelsToStop($input, $output, $tasks, $tasks->keys()->toArray()))) {
                $this->tools()->error('no labels selected to stop');

                return 1;
            }
        }

        $this->stopSelectedTasks($output, $tasks, $labels);

        return 0;
    }

    private function promptForLabelsToStop(InputInterface $input, OutputInterface $output, Collection $tasks, array $labels): int|array
    {
        $label = strtolower((string)$this->tools()->choose('Which task would you like to stop? ', $tasks->keys()->prepend( 'All & Daemon')->prepend('All')->toArray()));

        if ('all & daemon' === $label) {
            $this->tools()->info('Stopping all tasks and the mutagen daemon...');
            if ($this->getMutagen()->stop()) {
                $this->tools()->success('stopped all sessions and daemon successfully');

                return 0;
            }

            $this->tools()->error('failed to stop processes! Check mutagen status: <info>mutagen sync list</info>');
            return 1;

        } elseif ('all' !== $label) {
            $labels = [$label];
        }

        return $labels;
    }

    private function stopSelectedTasks(OutputInterface $output, Collection $tasks, array $labels): void
    {
        $this->tools()->info('Stopping <info>%s</info> sync tasks', count($labels));

        $tasks->only(...$labels)->each(function (SyncTask $task) use ($output) {
            if ($task->isRunning()) {
                return $this->stopTask($output, $task);
            }

            $this->tools()->info('task <info>%s</info> is not running', $task->getLabel());

            return true;
        });
    }

    private function stopTask(OutputInterface $output, SyncTask $task): bool
    {
        $command = new Collection(['mutagen', 'sync', 'terminate']);
        $command->add(sprintf('--label-selector=%s', $task->getLabel()));

        $proc = $this->runProcessViaHelper($output, $command);

        if ($proc->isSuccessful()) {
            $this->tools()->success('stopped session for <info>%s</info> successfully', $task->getLabel());
        } else {
            $this->tools()->error('failed to start session for <info>%s</info>; check options', $task->getLabel());
        }

        return true;
    }
}
