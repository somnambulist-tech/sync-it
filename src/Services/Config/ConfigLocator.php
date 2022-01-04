<?php declare(strict_types=1);

namespace SyncIt\Services\Config;

use RuntimeException;

/**
 * Class ConfigLocator
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\Config\ConfigLocator
 */
class ConfigLocator
{

    const ENV_NAME  = 'MUTAGEN_SYNC_IT_CONFIG';
    const FILE_NAME = '.mutagen_sync_it.yaml';

    public function locate(): string
    {
        if (false !== $file = $this->tryLocations()) {
            return $file;
        }

        throw new RuntimeException(
            sprintf('Failed to locate a config file in local project (%s) or from environment (%s)', static::FILE_NAME, static::ENV_NAME)
        );
    }

    private function tryLocations(): bool|string
    {
        if (getenv(static::ENV_NAME)) {
            return realpath(getenv(static::ENV_NAME));
        }
        if (isset($_ENV[static::ENV_NAME])) {
            return realpath(getenv(static::ENV_NAME));
        }

        return realpath(getcwd() . '/' . static::FILE_NAME);
    }
}
