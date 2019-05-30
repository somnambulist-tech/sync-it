# Sync-It for Mutagen (Mutagen Sync-It)

SyncIt is a helper (phar archive) to make it easier to work with sessions when
running [mutagen](https://mutagen.io/).

SyncIt runs from a config file typically located in your project root named:
`.mutagen_sync_it.yaml`. This file should be committed to your version control.

Inside the config file you can define any number of sync tasks. Each task
should be a unique combination of a source (the alpha) and a target (beta).

Currently SyncIt has been tested against mutagen 0.8.3. There is limited
support for 0.9.0 beta `--label=`.

SyncIt has only been tested on macOS Mojave.

## Features

 * simple yaml configuration
 * common options and ignore rules that are shared by tasks
 * all mutagen `create` flags are supported
 * labels (without 0.9.0 beta)
 * multiple tasks per project file
 * custom file location via a `MUTAGEN_SYNC_IT_CONFIG` env param
 * phar archive
 * support for .env files (including overrides)

## Setup

Grab the phar archive and copy it to `/usr/local/bin` or add it to your path.
Symlink the phar to `syncit` or a.n.other name.

In your project root e.g. `~/Projects/my-web-project` run: `syncit init` to
create a default, commented config file named `.mutagen_sync_it.yaml`.
Tweak the settings.

__Caution:__ before using mutagen ensure you have read and understood the docs.
This is provided as-is without warranty of any kind. Use at your own risk!

__Caution:__ mis-configuring a mutagen session can cause serious data-loss!

### Protecting Yourself From File Overwrites

By way of some safe-guards you can configure a global mutagen config by adding
a `.mutagen.toml` file to your home folder `cd ~`. This file defines global
defaults that will be applied to all sessions (see mutagen.io for more details)

```
[ignore]
default = [
    # System files
    ".DS_Store",
    "._*",

    # Vim files
    "*~",
    "*.sw[a-p]",

    # Common folders and files
    ".idea",
]
# ignore vcs files like .git .svn etc
vcs = true

[symlink]
mode = "ignore"

[sync]
# ensures beta cannot overwrite alpha
mode = "one-way-replica"

[permissions]
defaultFileMode=0644
defaultDirectoryMode=0755
```

The above ensures that all syncs are created using `one-way-replica`. This
ensures that no changes are written back to the source but the target will
be overwritten.

## The Config File

The config file is split into 2 sections:

 * common
 * tasks

### Sample Config

```yaml
mutagen:
    common:
        options:
            default-directory-mode: '0755'
            default-file-mode: '0644'
            ignore-vcs: ~
            symlink-mode: ignore

        ignore:
            - ".DS_Store"
            - "._*"
            - ".idea/"
            - "vendor/"
            - "var/"

    tasks:
        source_files:
            source: "${PROJECT_DIR}"
            target: "docker://container_1/app"
            options:
                sync-mode: one-way-replica
            ignore:
                - "composer.*"

        composer_json:
            source: "${PROJECT_DIR}/composer.json"
            target: "docker://container_1/app/composer.json"
            use_common: false
            options:
                sync-mode: two-way-safe

        composer_lock:
            source: "${PROJECT_DIR}/composer.lock"
            target: "docker://container_1/app/composer.lock"
            use_common: false
            options:
                sync-mode: two-way-safe
```

The config file will expand any configured env args using `${ENV_NAME}`
notation. `${PROJECT_DIR}` is an alias of `${PWD}`. This expansion is
done at run time only. For a full list of dedicated env vars and the
key to use to access it run: `syncit params`

__Note__: no attempt is made to hide or mask sensitive env vars. SyncIt
is intended for local dev usage only.

### Common

The common area (not to be confused with `global` from the .mutagen.toml
file); allows settings to be shared in the current project. Here you can
add safe guards if you share this with other people e.g.: one-way-replica
file permissions etc.

Options are the mutagen create flag names with the leading `--`. For flags
with no value, they must have a `~` as the value. Flags that require a value
where one is not set are not passed through to mutagen.

Read more: https://mutagen.io/documentation or use the `--help` option on
the mutagen command line program e.g.: `mutagen create --help`.

### Tasks

The tasks define each sync task. These are either folder copies or single file
copies. In the example above, the project source files are being copied to the
`/app` folder in a docker container, excluding any composer files.

The remaining 2 tasks, ensure that changes to composer.json/lock are sync'd
from the container back to the local dev. This ensures that composer can be
run in the container and on the local machine.

In both composer cases the common options are ignored.

Each task must have a unique name and the `source` and `target` must be specified.
The name is used as the label, and if running a version of mutagen >=0.9.0
this will be passed as the label when appropriate. Each task can optionally
override any existing option defined in `common`, prevent common settings being
used, and specify additional `ignore` rules.

Tasks will be matched to running sessions based on the `source` and `target`.
This allows the task name to be used consistently regardless of mutagen version.

The target supports different transport mechanisms e.g. docker:// ssh:// etc.
Be sure to read the format / rules at: https://mutagen.io/documentation/transports/

## Managing Tasks

SyncIt acts as a wrapper over `mutagen create|terminate|list` in a pretty basic
manner. Before continuing you should start the mutagen daemon:
`mutagen daemon start`. Many commands will not run if the mutagen process is not
running.

To start all configured tasks: `syncit start`. You will be prompted to start a
specific task, or everything.

To start a specific task: `syncit start --label=<label>`

To stop all configured tasks: `syncit stop`. You will be prompted to stop a
specific task, or everything.

To stop a specific task: `syncit stop --label=<label>`

Extra information can be obtained by running: `syncit view <label>` or if no
label is given, the available labels will be displayed.

To get an overview of running tasks: `syncit status`

__Note:__ sessions are mapped to tasks by the combination of source and target;
if these paths do not match the session will not be linked. Once labels are
fully available, this limitation should be removed. In particular this means
trailing slashes e.g.: `source: ${PROJECT_DIR}/` should be removed as mutagen will
remove it anyway.

## .env Files

SyncIt will read a `.env` file if it finds one in the project root. This is
processed using Symfony DotEnv and will support `.env.local` and other
overrides.

## Building the phar archive

To build the phar archive, first checkout / clone the sync-it project, run
`composer install` and ensure that `phar.readonly` is set to `0` in your
php.ini.
 
You can then run: `bin/compile` which will create a `mutagen-sync-it.phar` file
in the project root. The compile will output the SHA384 hash together with the
file location / name.

## Issues / Questions

Make an issue on the github repo: https://github.com/dave-redfern/somnambulist-sync-it/issues
Pull requests are welcome!

## Other Options

A similar project in python: https://github.com/gfi-centre-ouest/mutagen-helper
