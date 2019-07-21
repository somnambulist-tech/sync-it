<?php

declare(strict_types=1);

namespace SyncIt\Services;

use RuntimeException;

/**
 * Class DockerContainerResolver
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\DockerContainerResolver
 */
class DockerContainerResolver
{

    /**
     * Attempts to locate a running container using the specified name
     *
     * docker ps is used with a name filter. The output is formatted to return either
     * the container name or the container id, depending on the format option. The
     * name is more human friendly, the id is the hex string for that container.
     *
     * If multiple matches are found, then an exception is raised as it would make this
     * function ambiguous.
     *
     * @param string $name
     * @param string $format Either id or name, default id
     *
     * @return string
     * @throws RuntimeException
     *
     * @link https://docs.docker.com/engine/reference/commandline/ps/#formatting
     */
    public function containerFromNameFilter(string $name, string $format = 'id'): string
    {
        $format   = $format == 'name' ? '.Names' : '.ID';

        $command  = 'docker ps --no-trunc';
        $command .= sprintf(' --format="{{%s}}"', $format);
        $command .= sprintf(' --filter=name="%s"', $name);

        $success    = null;
        $containers = [];

        /*
         * exec is used here because SF\Process was producing no output, but running OK.
         */
        exec($command, $containers, $success);

        if (0 !== $success) {
            throw new RuntimeException(sprintf('Unable to query docker, exit code was "%s"', $success));
        }
        if (count($containers) == 0) {
            throw new RuntimeException(sprintf('No containers found matching name "%s"', $name));
        }
        if (count($containers) > 1) {
            throw new RuntimeException(
                sprintf('Multiple matches for "%s"; use a more specific name ("%s")', $name, implode('", "', $containers))
            );
        }

        return trim($containers[0]);
    }
}
