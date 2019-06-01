<?php

declare(strict_types=1);

namespace SyncIt\Commands;

use Somnambulist\Collection\Collection;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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

    use ListConfiguredTasks;
    use RunWrappedProcess;

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Starts the configured mutagen sync tasks')
            ->addOption('label', 'l', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'The task label(s) to start (mutagen >0.9.0)')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List available tasks')
            ->setHelp(<<<'HELP'
Starts the specified, or all, configured sync tasks as defined in the current
projects config file.

To start all tasks run:

  <info>php %command.full_name%</info>

To start individual tasks run:

  <info>php %command.full_name% --label=task</info>

Start multiple tasks by using multiple --label calls:

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list')) {
            $table = $this->buildTaskTableHelper($output);
            $table->render();

            return 0;
        }

        $this->getMutagen()->assertDaemonIsRunning($input, $output);

        $tasks = $this->getMutagen()->getSessions()->map($this->getConfig()->getTasks());

        if (!count($labels = $input->getOption('label'))) {
            $labels = $tasks->keys()->toArray();
        }
        if (!count($input->getOption('label'))) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Which task would you like to start? ', $tasks->keys()->add('All')->toArray());

            $label = $helper->ask($input, $output, $question);
            if ($label !== 'All') {
                $labels = [$label];
            }
        }

        $output->writeln(sprintf('Starting <info>%s</info> sync tasks', count($labels)));

        $tasks->only($labels)->each(function (SyncTask $task) use ($output) {
            if ($task->isRunning()) {
                $output->writeln(sprintf('<fg=white;bg=blue> RUN </> task <fg=yellow>"%s"</> is already running', $task->getLabel()));
                return true;
            }

            $command = $this->buildStartCommand($task);
            $proc    = $this->runProcessViaHelper($output, $command);

            if ($proc->isSuccessful()) {
                $output->writeln(
                    sprintf('<fg=black;bg=green> RUN </> started session for <fg=yellow>"%s"</> successfully', $task->getLabel())
                );
            } else {
                $output->writeln(
                    sprintf('<error> ERR </error> failed to start session for <fg=yellow>"%s"</>; check options', $task->getLabel())
                );
            }

            return true;
        });

        return 0;
    }

    private function buildStartCommand(SyncTask $task): Collection
    {
        $command = new Collection([
            'mutagen', 'create',
            $task->getSource(),
            $task->getTarget(),
        ]);

        if ($this->getMutagen()->hasLabels()) {
            $command->add(sprintf('--label="%s"', $task->getLabel()));
        }

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
