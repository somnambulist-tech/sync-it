<?php

declare(strict_types=1);

namespace SyncIt\Services;

use Phar;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Compiler
 *
 * Shamelessly ripped from Composer.
 *
 * @link https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */
class Compiler
{

    private function basePath(): string
    {
        return __DIR__ . '/../..';
    }

    /**
     * Compiles PPM into a single phar file
     *
     * @param string $pharFile The full path to the file to create
     *
     * @throws RuntimeException
     */
    public function compile($pharFile = 'mutagen-sync-it.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new Phar($pharFile, 0, 'mutagen-sync-it.phar');
        $phar->startBuffering();

        $finderSort = function (SplFileInfo $a, SplFileInfo $b) {
            return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
        };

        $basePath = $this->basePath();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('LICENSE')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->notName('create-phar.php')
            ->notName('Compiler.php')
            ->in($basePath . '/src/')
            ->in($basePath . '/vendor/beberlei/')
            ->in($basePath . '/vendor/eloquent/')
            ->in($basePath . '/vendor/psr/')
            ->in($basePath . '/vendor/somnambulist/')
            ->in($basePath . '/vendor/symfony/')
            ->sort($finderSort)
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/autoload.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_files.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_real.php'));
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/autoload_static.php'));
        if (file_exists($basePath . '/vendor/composer/include_paths.php')) {
            $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/include_paths.php'));
        }
        $this->addFile($phar, new SplFileInfo($basePath . '/vendor/composer/ClassLoader.php'));

        $this->addBin($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);

        chmod($pharFile, 0755);
    }

    private function addFile(Phar $phar, SplFileInfo $file, $strip = true): void
    {
        $path = strtr(str_replace(realpath($this->basePath()), '', $file->getRealPath()), '\\', '/');

        $content = file_get_contents($file->getRealPath());
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n" . $content . "\n";
        }

        $phar->addFromString($path, $content);
    }

    private function addBin(Phar $phar)
    {
        $content = file_get_contents($this->basePath() . '/bin/console');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/console', $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace(string $source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output     .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub(): string
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php

// Copied from Composer stub
// Avoid APC causing random fatal errors per https://github.com/composer/composer/issues/264
if (extension_loaded('apc') && ini_get('apc.enable_cli') && ini_get('apc.cache_by_default')) {
    if (version_compare(phpversion('apc'), '3.0.12', '>=')) {
        ini_set('apc.cache_by_default', 0);
    } else {
        fwrite(STDERR, 'Warning: APC <= 3.0.12 may cause fatal errors when running ppm commands.'.PHP_EOL);
        fwrite(STDERR, 'Update APC, or set apc.enable_cli or apc.cache_by_default to 0 in your php.ini.'.PHP_EOL);
    }
}

Phar::mapPhar('mutagen-sync-it.phar');
require 'phar://mutagen-sync-it.phar/bin/console';

__HALT_COMPILER();
EOF;
    }
}
