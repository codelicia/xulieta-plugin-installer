<?php

declare(strict_types=1);

namespace Codelicia\Xulieta\AutoPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Link;
use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use JetBrains\PhpStorm\ArrayShape;

final class Register implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public static function scan(Event $composerEvent): void
    {
        $composerEvent->getIO()->write('Plugin is actually running');

        // @TODO list all xulieta packages
        // @TODO check for all xulieta packages, which one is configured with auto-enabling
        // @TODO check in the config file if plugin is enabled
        // @TODO provide a flag to turn off "auto-enabled installed packs"
    }

    #[ArrayShape([
        PackageEvents::POST_PACKAGE_INSTALL   => "string",
        PackageEvents::POST_PACKAGE_UPDATE    => "string",
        PackageEvents::POST_PACKAGE_UNINSTALL => "string",
    ])]
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL   => 'onPostPackageInstall',
            PackageEvents::POST_PACKAGE_UPDATE    => 'onPostPackageUpdate',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackageUninstall',
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }
}
