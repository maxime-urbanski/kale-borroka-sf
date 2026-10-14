<?php

declare(strict_types=1);

namespace App\Tests\Deploy;

use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * What the VPS deploy (docs/deployment.md) relies on from the application's configuration.
 */
class DeploymentConfigTest extends KernelTestCase
{
    use ServiceTrait;

    /**
     * Outside a request (CLI commands), absolute URLs come from DEFAULT_URI, which each
     * environment sets to its own https:// domain.
     */
    public function testUrlsBuiltOutsideARequestUseTheDefaultUri(): void
    {
        $defaultUri = $_SERVER['DEFAULT_URI'] ?? $_ENV['DEFAULT_URI'] ?? null;
        self::assertIsString($defaultUri);

        $url = self::service(UrlGeneratorInterface::class, 'router')
            ->generate('app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // The generator leaves out a default port (https://localhost:443 → https://localhost).
        self::assertSame(parse_url($defaultUri, \PHP_URL_SCHEME), parse_url($url, \PHP_URL_SCHEME));
        self::assertSame(parse_url($defaultUri, \PHP_URL_HOST), parse_url($url, \PHP_URL_HOST));
    }

    /**
     * Each deploy replaces the php container: the sessions (the cart lives in them) must be
     * on the volume compose.prod.yaml mounts, not in the container's /tmp.
     */
    public function testSessionsAreStoredOnTheSessionsVolume(): void
    {
        $savePath = self::getContainer()->getParameter('session.save_path');
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($savePath);
        self::assertIsString($projectDir);
        self::assertStringStartsWith($projectDir.'/var/sessions/', $savePath);

        $compose = Yaml::parseFile($projectDir.'/compose.prod.yaml');
        self::assertIsArray($compose);
        self::assertContains('sessions:/app/var/sessions', $compose['services']['php']['volumes'] ?? []);
    }
}
