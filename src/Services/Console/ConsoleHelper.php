<?php declare(strict_types=1);

namespace SyncIt\Services\Console;

use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use function sprintf;

/**
 * Class ConsoleHelper
 *
 * @package    SyncIt\Services\Console
 * @subpackage SyncIt\Services\Console\ConsoleHelper
 */
class ConsoleHelper
{

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var HelperSet
     */
    private $helperSet;

    /**
     * @var bool
     */
    private $noOutput = false;

    /**
     * Constructor
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $this->helperSet = new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            new QuestionHelper(),
        ]);
    }

    public function disableOutput(): void
    {
        $this->noOutput = true;
    }

    public function enableOutput(): void
    {
        $this->noOutput = false;
    }

    public function input(): InputInterface
    {
        return $this->input;
    }

    public function execute(string $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = null): bool
    {
        $h = new ProcessHelper();
        $h->setHelperSet($this->helperSet);

        $proc = Process::fromShellCommandline($command, $cwd, $env, $input, $timeout);

        $h->run($this->output, $proc);

        return $proc->isSuccessful();
    }

    public function ask(string $question, bool $confirm = true, string ...$args)
    {
        $h      = new QuestionHelper();
        $h->setHelperSet($this->helperSet);

        $result = $h->ask($this->input, $this->output, new Question('<q> Q </q> ' . sprintf($question, ...$args)));

        if ($confirm) {
            $conf = new Question(sprintf('<i> ▲ </i> You provided "<info>%s</info>", is this correct? [y/n] ', $result));

            if ('y' !== $h->ask($this->input, $this->output, $conf)) {
                return $this->ask($question, $confirm);
            }
        }

        return $result;
    }

    public function choose(string $question, array $choices = [], $default = null)
    {
        $h = new QuestionHelper();
        $h->setHelperSet($this->helperSet);

        $c = new ChoiceQuestion('<q> Q </q> ' . $question, $choices, $default);

        return $h->ask($this->input, $this->output, $c);
    }

    public function warning(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<warn> ▲ </warn> ' . $message, ...$args));
    }

    public function info(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<i> ℹ︎ </i> ' . $message, ...$args));
    }

    public function success(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<ok> ✔ </ok> ' . $message, ...$args));
    }

    public function error(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<err> ✖ </err> ' . $message, ...$args));
    }

    public function step($step, string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<step> %s </step> ' . $message, $step, ...$args));
    }

    public function question(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf('<q> Q </q> ' . $message, ...$args));
    }

    public function message(string $message, ...$args): void
    {
        $this->noOutput ?: $this->output->writeln(sprintf($message, ...$args));
    }

    public function newline(): void
    {
        $this->noOutput ?: $this->output->writeln('');
    }
}
