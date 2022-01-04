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
 * Class StartCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\StartCommand
 */
class StartCommand extends BaseCommand
{
    use GetLabelsFromInput;
    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure(): void
    {
        $this
            ->setName('start')
            ->setDescription('Starts the configured mutagen sync tasks')
            ->addArgument('label', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, 'The labels/group to start or all to start all', [])
            ->addOption('label', 'l', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'The task label(s) to start or group name')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
            ->setHelp(<<<'HELP'
Starts the specified, or all, configured sync tasks as defined in the current
projects' config file.

To start all tasks run:

  <info>php %command.full_name% all</info>

To be prompted what to start run:

  <info>php %command.full_name%</info>

To start individual tasks run:

  <info>php %command.full_name% task</info> or:
  <info>php %command.full_name% --label=task</info>

Start multiple tasks by using multiple --label calls:

  <info>php %command.full_name% task1 task2</info> or:
  <info>php %command.full_name% --label=task1 --label=task2</info>

List the available tasks:

  <info>php %command.full_name% --list</info>

If a task will not start, turn on debugging to get the output from the
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
            $label = $this->tools()->choose('Which task would you like to start? ', $tasks->keys()->prepend('All')->toArray());

            if ($label !== 'All') {
                $labels = [$label];
            } else {
                $labels = $tasks->keys()->toArray();
            }
        }

        $output->writeln(sprintf('Starting <info>%s</info> sync tasks', count($labels)));

        $tasks->only(...$labels)->each(function (SyncTask $task) use ($output) {
            if ($task->isRunning()) {
                $this->tools()->info('task <comment>%s</> is already running', $task->getLabel());
                return true;
            }

            $command = $this->buildStartCommand($task);
            $proc    = $this->runProcessViaHelper($output, $command);

            if ($proc->isSuccessful()) {
                $this->tools()->success('started session for <info>%s</> successfully', $task->getLabel());
            } else {
                $this->tools()->error('failed to start session for <info>%s</>; check options', $task->getLabel());
            }

            return true;
        });

        return 0;
    }

    private function buildStartCommand(SyncTask $task): Collection
    {
        $command = new Collection([
            'mutagen', 'sync', 'create',
            $task->getSource(),
            $task->getTarget(),
        ]);
        $command->add(sprintf('--label="%s"', $task->getLabel()));

        $task->getOptions()->each(function ($value, $key) use ($command) {
            if (is_null($value) && in_array($key, ['ignore-vcs', 'no-ignore-vcs'])) {
                $command->add(sprintf('--%s', $key));
                return true;
            }
            if (is_null($value)) {
                return true;
            }

            $command->add(sprintf('--%s=%s', $key, $value));

            return true;
        });
        $task->getIgnore()->each(function ($value) use ($command) {
            $command->add(sprintf('--ignore="%s"', $value));
        });

        return $command;
    }
}
