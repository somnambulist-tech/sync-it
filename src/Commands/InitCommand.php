<?php declare(strict_types=1);

namespace SyncIt\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SyncIt\Services\Config\ConfigLocator;

/**
 * Class InitCommand
 *
 * @package    SyncIt\Commands
 * @subpackage SyncIt\Commands\InitCommand
 */
class InitCommand extends BaseCommand
{

    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Create a new config file in the current working directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = getcwd() . DIRECTORY_SEPARATOR . ConfigLocator::FILE_NAME;

        if (file_exists($file)) {
            if ('n' === $this->tools()->ask('<comment>Warning</comment> a config file already exists, do you wish to <error>overwrite</> it? (y/n) ', false)) {
                if ('y' === $this->tools()->ask('Would you like to see a sample config? (y/n) ', false)) {
                    $this->tools()->info('Outputting <comment>sample</comment> config:');
                    $output->writeln(str_repeat('-', 80));
                    $output->write($this->default());
                }
            }

            return 0;
        }

        if (false !== file_put_contents($file, $this->default())) {
            $this->tools()->success('config file <info>%s</> created successfully', $file);
        } else {
            $this->tools()->error('failed to create config at <info>%s</>', $file);
        }

        return 0;
    }

    private function default(): string
    {
        $date = date('Y-m-d H:i:s');

        return <<<YAML
#
# Sync-It with Mutagen Config File
# Created: $date
#
# Various environment parameters can be used in the config file by using
# \${ENV_VAR_NAME}. They will be processed and merged into the config as
# it is loaded.
#
# Debug available params by using: "./bin/console params"; note: this
# makes no attempt to hide sensitive env vars.
#

mutagen:
    common:
        # a project name for the labels; the same label could exist across projects
        # this prefix allows to set a per-project name e.g. admin or users etc.
        label_prefix: ~
        
        # main options not including ignore rules
        options:
            # see: https://mutagen.io/documentation/permissions/
            # octal as a string, must be quoted e.g. '0755'
            default-directory-mode: ~
            default-file-mode: ~
            
            # see: https://mutagen.io/documentation/permissions/
            # remove if not needed
            #default-group: "mygroup"
            #default-user: "myuser"
            
            # ignore all VCS files; needs a ~ as it's a flag to mutagen
            #ignore-vcs: ~
        
        # any common regex rules to ignore files / folders; will be merged into each session
        ignore:
            #- ".DS_Store"
            #- "._*"
            #- ".idea/"
        
        # any other config can be specified and will be passed to mutagen create
        # see: mutagen create --help
    
    tasks:
        # each session needs a unique key, this will be used as the label (from mutagen >0.9)
        default:
            # the local folder or single file to sync
            source: "path to source"
            # see: https://mutagen.io/documentation/transports/
            # can be a (running!) docker container, ssh endpoint etc
            # resolve docker containers to id:
            #    docker://{docker:name=my-container-name}/folder/to/copy/to
            # resolve docker containers to a name:
            #    docker://{docker:name=my-container-name:name}/folder/to/copy/to
            target: "path to target"
            # prevent the common config being used by setting to false
            #use_common: false
            
            options:
                #sync-mode: one-way-replica

                #symlink-mode: ignore
                #ignore-vcs: ~

            ignore:
                - "vendor/"
                - "var/"
                - "composer.*"

YAML;
    }
}
