<?php declare(strict_types=1);

namespace SyncIt\Services;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use SyncIt\Models\Sessions;
use function sprintf;

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

    const MUTAGEN_MIN_VERSION = '0.10.0';

    const DAEMON_START      = 'mutagen daemon start';
    const DAEMON_STOP       = 'mutagen daemon stop';
    const DAEMON_STOP_TASKS = 'mutagen sync terminate -a';

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
        $this->assertSupportedVersion();

        exec(static::DAEMON_START);

        return $this->isRunning();
    }

    public function stop(): bool
    {
        $this->assertSupportedVersion();

        if ($this->isRunning()) {
            exec(static::DAEMON_STOP_TASKS);
            exec(static::DAEMON_STOP);
        }

        return !$this->isRunning();
    }

    public function assertSupportedVersion(): void
    {
        if (!$this->supported()) {
            throw new RuntimeException(sprintf('Mutagen update required, min supported version is "%s"', self::MUTAGEN_MIN_VERSION));
        }
    }

    public function assertDaemonIsRunning(InputInterface $input, OutputInterface $output): void
    {
        $this->assertSupportedVersion();

        if ($this->isRunning()) {
            return;
        }

        $output->write('Mutagen is not running, attempting to start...');

        if ($this->start()) {
            $output->writeln('<info>OK</info>');
            return;
        }

        $output->writeln('<err>Failed</err>');

        throw new RuntimeException('Failed to start mutagen, is it available in your path?');
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

    public function supported(): bool
    {
        return version_compare($this->getVersion(), self::MUTAGEN_MIN_VERSION, '>=');
    }

    public function getSessions(): Sessions
    {
        return (new MutagenSessionParser())->sessions();
    }
}
