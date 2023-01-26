# Sync-It for Mutagen (Mutagen Sync-It)

[![GitHub Actions Build Status](https://img.shields.io/github/actions/workflow/status/somnambulist-tech/sync-it/release.yml?logo=github&branch=master)](https://github.com/somnambulist-tech/sync-it/actions?query=workflow%3Arelease)
[![Issues](https://img.shields.io/github/issues/somnambulist-tech/sync-it?logo=github)](https://github.com/somnambulist-tech/sync-it/issues)
[![License](https://img.shields.io/github/license/somnambulist-tech/sync-it?logo=github)](https://github.com/somnambulist-tech/sync-it/blob/master/LICENSE)

SyncIt is a helper (phar archive) to make it easier to work with sessions when
running [mutagen](https://mutagen.io/).

SyncIt runs from a config file typically located in your project root named:
`.mutagen_sync_it.yaml`. This file should be committed to your version control.

Inside the config file you can define any number of sync tasks. Each task
should be a unique combination of a source (the alpha) and a target (beta).

SyncIt requires Mutagen >=0.10.0.

There is support for labels including project prefixes.

SyncIt has only been tested on macOS Mojave.

## Features

 * simple yaml configuration
 * common options and ignore rules that are shared by tasks
 * all mutagen `create` flags are supported
 * labels
 * multiple tasks per project file
 * custom file location via a `MUTAGEN_SYNC_IT_CONFIG` env param
 * phar archive
 * support for .env files (including overrides)
 * docker container resolution from a specified name
 * groups to start/stop multiple tasks at the same time

## Setup

Grab the phar archive and copy it to `/usr/local/bin` or add it to your path.
Symlink the phar to `syncit` or a.n.other name.

Or install via brew:

```bash
brew install somnambulist-tech/somnambulist/syncit
```

In your project root e.g. `~/Projects/my-web-project` run: `syncit init` to
create a default, commented config file named `.mutagen_sync_it.yaml`.
Tweak the settings.

__Caution:__ before using mutagen ensure you have read and understood the docs.
This is provided as-is without warranty of any kind. Use at your own risk!

__Caution:__ mis-configuring a mutagen session can cause serious data-loss!

### Removing SyncIt

Remove any symlinks you have and delete the phar file. No other files are created
except for any config yaml files. Again: use the following script at your own risk!

```bash
unlink /usr/local/bin/syncit && rm -v /usr/local/bin/mutagen-sync-it.phar
```

### Protecting Yourself From File Overwrites

By way of some safeguards you can configure a global mutagen config by adding
a `.mutagen.yml` file to your home folder `cd ~`. This file defines global defaults
that will be applied to all sessions (see mutagen.io for more details):

```
sync:
    defaults:
        mode: one-way-replica
        ignore:
            vcs: true
            paths:
                # System files
                - ".DS_Store"
                - "._*"
                  
                # Vim files
                - "*~"
                - "*.sw[a-p]"

                # Common folders and files
                - ".idea"
        symlink:
            mode: ignore
        permissions:
            defaultFileMode: 0644
            defaultDirectoryMode: 0755
```

The above ensures that all syncs are created using `one-way-replica`. This
ensures that no changes are written back to the source but the target will
be overwritten.

__Note:__ that this will happily override your local if the source is set as a remote
and the target is a local folder. Be very careful!

## The Config File

The config file is split into 2 sections:

 * common
 * tasks

### Sample Config

```yaml
mutagen:
    common:
        label_prefix: ~

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
            groups:
                - group1
                - group2
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
add safeguards if you share this with other people e.g.: one-way-replica
file permissions etc.

Options are the mutagen create flag names with the leading `--`. For flags
with no value, they must have a `~` as the value. Flags that require a value
where one is not set are not passed through to mutagen.

Read more: https://mutagen.io/documentation or use the `--help` option on
the mutagen command line program e.g.: `mutagen create --help`.

Since 1.0.0-beta1 `label_prefix` has been added to the common section. This allows
a common prefix e.g. a project name to be prefixed to all the task labels. Useful
if you use common labels with multiple projects.

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

Multiple group names can be set on each tasks under the `groups:` entry. This 
will allow start/stop to work with all those tasks tagged with that group name.
For example: a web app with a JavaScript build pipeline may have many tasks. These
can now be grouped into app/build making it easier to start/stop in one go.

SyncIt should work with any of the Mutagen.io transports however please note that
tasks are not started via an interactive process, therefore you will not be prompted
for any passwords. If you need to use SSH to sync changes be sure to set up SSH key
login to avoid the need for passwords.

#### Docker Containers

When using docker; to make life easier you should use named containers with
predictable / repeatable names via the `--name` flag on `docker run` or use
docker compose and use a service name or, especially in dev, a specific name
can be set using `container_name:`. If the latter is chosen, the container name
will be exactly this value but there can only be one of them.

Alternatively: the target container name can be set to use a name that will
attempt to be resolved from the running available containers as defined in
the output from `docker ps`. To do this, change the container name to be:
`{docker:name=<some_keywords_to_match>}` - don't include the `<>`. The name
can contain any valid characters that a container name can have. The default
that will be substituted is the matching container hex ID. Alternatively the
matching container name can be used by adding `:name` to the string.

Doing this is the equivalent of performing:

```
$ docker ps --no-trunc --format="{{.ID}}" --filter=name="my-container"
$ docker ps --no-trunc --format="{{.Names}}" --filter=name="my-container"
```

The configuration using the name resolution would look like the following:

```yaml
composer_lock:
    source: "${PROJECT_DIR}/composer.lock"
    target: "docker://{docker:name=my-container:name}/app/composer.lock"
```

The resulting output from "view" would then contain:

```
| Target (beta)        | docker://1fac35046452b0f6d7de0167399d6f4f7f68968ca3a844a385d |
|                      | b5ff361df4717/app                                            |
```

While using `:name` would give:

```
| Target (beta)        | docker://my-project_my-container_1/app                       |
```

__Note:__ the chosen name must result in only **1** container. If none or more than
one are found, SyncIt will raise an error. In the case of multiple matches, all
matched names will be in the error (useful when used with `:name`).

__Note:__ name resolution is performed after .env resolution. So you can use a
ENV parameter for the container name and share it between the SyncIt config file
and a docker-compose.yml file.

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
in the project root. The compilation will output the SHA384 hash together with the
file location / name.

## Issues / Questions

Make an issue on the GitHub repo: https://github.com/somnambulist-tech/sync-it/issues

Pull requests are welcome!

## Other Options

A similar project in python: https://github.com/gfi-centre-ouest/mutagen-helper
