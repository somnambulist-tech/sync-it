<?php declare(strict_types=1);

namespace SyncIt\Services\Config;

use Assert\Assert;
use Somnambulist\Collection\MutableCollection as Collection;
use Symfony\Component\Yaml\Yaml;
use SyncIt\Models\Config;
use SyncIt\Models\SyncTask;
use SyncIt\Services\DockerContainerResolver;

/**
 * Class ConfigParser
 *
 * @package    SyncIt\Services
 * @subpackage SyncIt\Services\Config\ConfigParser
 */
class ConfigParser
{

    /**
     * @var DockerContainerResolver
     */
    private $containerResolver;

    public function __construct(DockerContainerResolver $containerResolver)
    {
        $this->containerResolver = $containerResolver;
    }

    /**
     * Converts a Yaml file into a Config object, replacing params
     *
     * Config contains several sets of processed data objects, and handles merging
     * common configuration into the defined tasks.
     *
     * @param string $config
     *
     * @return Config
     */
    public function parse(string $config): Config
    {
        $config = Yaml::parse($this->replaceVars($config));

        Assert::lazy()->tryAll()
            ->that($config, 'config')->keyExists('mutagen', 'The config file should start with a "mutagen" section')
            ->that($config['mutagen'], 'config')->keyExists('tasks', 'The config file should contain a "tasks" section')
            ->verifyNow()
        ;

        $common = Collection::collect($config['mutagen']['common']);
        $params = Collection::collect($this->getEnvParameters());
        $tasks  = $this->createTasksFrom($config['mutagen']['tasks'], $common);

        return new Config($common, $tasks, $params);
    }

    private function createTasksFrom(array $configTasks, Collection $common): Collection
    {
        $tasks = new Collection();

        foreach ($configTasks as $label => $data) {
            Assert::lazy()->tryAll()
                ->that($data, $label)->keyExists('source', 'The "source" key is required for the task')
                ->that($data, $label)->keyExists('target', 'The "target" key is required for the task')
                ->verifyNow()
            ;

            $label   = $common->hasValueFor('label_prefix') ? sprintf('%s_%s', $common->get('label_prefix'), $label) : $label;
            $data    = Collection::collect($data);
            $options = $data->value('options', new Collection())->unique();
            $ignore  = $data->value('ignore', new Collection())->unique();
            $groups  = $data->value('groups', new Collection())->unique();

            if ($data->get('use_common', true)) {
                $options = $common
                    ->value('options', new Collection())
                    ->merge($data->value('options', new Collection()))
                ;
                $ignore  = $common
                    ->value('ignore', new Collection())
                    ->merge($data->value('ignore', new Collection()))
                    ->removeNulls()
                    ->unique()
                ;
            }

            $tasks->set(
                $label,
                new SyncTask(
                    $label,
                    (string)$data->get('source'),
                    (string)$data->get('target'),
                    (bool)$data->get('use_common', true),
                    $options,
                    $ignore,
                    $groups
                )
            );
        }

        return $tasks;
    }

    private function getEnvParameters(): array
    {
        $gEnv = Collection::collect($_ENV)->except('SYMFONY_DOTENV_VARS', 'PATH')->removeNulls();
        $pEnv = Collection::collect(getenv())->except('SYMFONY_DOTENV_VARS', 'PATH')->removeNulls();

        $params = array_merge(
            [
                '${PROJECT_DIR}' => getcwd(),
            ],
            array_combine(
                $pEnv->keys()->map(function ($value) {return sprintf('${%s}', strtoupper($value)); })->toArray(),
                $pEnv->values()->toArray()
            ),
            array_combine(
                $gEnv->keys()->map(function ($value) {return sprintf('${%s}', strtoupper($value)); })->toArray(),
                $gEnv->values()->toArray()
            )
        );

        ksort($params);

        return $params;
    }

    private function replaceVars(string $config): string
    {
        return $this->resolveContainerNames(strtr(
            $config,
            $this->getEnvParameters()
        ));
    }

    private function resolveContainerNames($config): string
    {
        $matches = [];

        $res = preg_match_all('/\{docker:name=(?P<service>[\w_-]+):?(?<format>(id|name))?\}/', $config, $matches);

        if (!$res) {
            return $config;
        }

        $labels       = $matches[0];
        $replacements = [];

        foreach ($labels as $match => $parameter) {
            $replacements[$match] = $this->containerResolver->containerFromNameFilter(
                $matches['service'][$match],
                $matches['format'][$match] ?? 'id'
            );
        }

        return str_replace($labels, $replacements, $config);
    }
}
