<?php

declare(strict_types=1);

namespace SyncIt\Services;

use RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use SyncIt\Models\Sessions;

/**
 * Class Mutagen
 *
 * Helper class to aggregate assorted calls / info about the installed
 * mutagen process.
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\Mutagen
 */
class Mutagen
{

    const DAEMON_START = 'mutagen daemon start';
    const DAEMON_STOP  = 'mutagen daemon stop';

    /**
     * @var string
     */
    private $version;

    public function isRunning(): bool
    {
        exec('pgrep mutagen', $pids);

        return !empty($pids);
    }

    public function start(): bool
    {
        exec(static::DAEMON_START);

        return $this->isRunning();
    }

    public function stop(): bool
    {
        if ($this->isRunning()) {
            exec('mutagen terminate -a');
            exec(static::DAEMON_STOP);
        }

        return !$this->isRunning();
    }

    public function assertDaemonIsRunning(InputInterface $input, OutputInterface $output, bool $askToStart = true): void
    {
        if ($this->isRunning()) {
            return;
        }
        if ($askToStart) {
            $question = new Question('Would you like to start the daemon? (y/n) ', false);

            if ('y' === strtolower((string)(new QuestionHelper())->ask($input, $output, $question))) {
                if ($this->start()) {
                    return;
                }

                throw new RuntimeException('Failed to start mutagen, is it available in your path?');
            }
        }

        throw new RuntimeException(sprintf('The mutagen daemon is not running, run: "%s"', static::DAEMON_START));
    }

    public function getVersion(): string
    {
        if (!$this->version) {
            $proc = new Process(['mutagen', 'version']);
            $proc->run();

            $this->version = trim($proc->getOutput());
        }

        return $this->version;
    }

    public function hasLabels(): bool
    {
        return version_compare($this->getVersion(), '0.9.0', '>=');
    }

    public function getSessions(): Sessions
    {
        return (new MutagenSessionParser())->sessions();
    }
}
