<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Every PHP file of the app and its tests declares strict types: without it, PHP silently
 * casts arguments ("12abc" into an int with a warning, 1.9 into 1).
 */
class StrictTypesTest extends TestCase
{
    public function testEveryFileDeclaresStrictTypes(): void
    {
        $missing = [];
        $root = \dirname(__DIR__, 2);

        foreach (['src', 'tests', 'migrations'] as $directory) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/'.$directory, \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ('php' === $file->getExtension() && !preg_match('/^<\?php\s+(?:\/\*.*?\*\/\s*)?declare\(strict_types=1\);/s', (string) file_get_contents((string) $file))) {
                    $missing[] = substr((string) $file, \strlen($root) + 1);
                }
            }
        }

        sort($missing);
        self::assertSame([], $missing);
    }
}
