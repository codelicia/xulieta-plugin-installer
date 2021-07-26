<?php

declare(strict_types=1);

namespace Codelicia\Xulieta\AutoPlugin;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
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
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Based on https://github.com/laminas/laminas-component-installer/blob/2.5.x/src/ComponentInstaller.php
 *
 * In order to have your xulieta extension auto configurable, you need to put in
 * your composer.json the following keys, if applicable:
 *
 * - extra.xulieta.parser
 * - extra.xulieta.validator
 *
 * The values should have the FQCN as the following example:
 *
 * <code class="lang-javascript">
 * {
 *   "extra": {
 *     "xulieta": {
 *       "parser": ["Malukenho\\QuoPrimumTempore\\JsonParser"],
 *       "validator": ["Malukenho\\QuoPrimumTempore\\JsonValidator"]
 *     }
 *   }
 * }
 * </code>
 */
final class Register implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function scan(PackageEvent $event): void
    {
        if (! $event->isDevMode()) {
            return;
        }

        $operation = $event->getOperation();
        assert($operation instanceof InstallOperation);
        $package = $operation->getPackage();
        $name = $package->getName();

        /** @var array<string,mixed> $packageExtra */
        $packageExtra = $package->getExtra();
        $extra = self::getExtraMetadata($packageExtra);

//        if (empty($extra)) {
        // Package does not define anything of interest; do nothing.
//            return;
//        }

        $extra = [
            "parser"    => ["Malukenho\\QuoPrimumTempore\\JsonParser"],
            "validator" => ["Malukenho\\QuoPrimumTempore\\JsonValidator"],
        ];

        self::injectModuleIntoConfig($extra, $event->getIO(), $event->getComposer());
    }

    /**
     * Retrieve the metadata from the "extra" section
     *
     * @param array<string,mixed> $extra
     *
     * @return array<string,mixed>
     */
    private static function getExtraMetadata(array $extra): array
    {
        $xulietaPluginConfiguration = [];

        if (isset($extra['xulieta']) && is_array($extra['xulieta'])) {
            /** @var array<string,mixed> $xulietaPluginConfiguration */
            $xulietaPluginConfiguration = $extra['xulieta'];
        }

        return $xulietaPluginConfiguration;
    }

    private static function injectModuleIntoConfig(array $extra, IOInterface $io, Composer $composer): void
    {
        $rootDir = dirname($composer->getConfig()->getConfigSource()->getName());
        $xulietaConfigFile = $rootDir . '/xulieta.xml';

        if (! file_exists($xulietaConfigFile)) {
            return;
        }


        // FIXME: Filter elements so it doesn't get repeated
        $xml = XmlUtils::loadFile($xulietaConfigFile);
        $root = $xml->documentElement;

        $xml->preserveWhiteSpace = true;
        $xml->formatOutput = true;

        $parsers = $root->getElementsByTagName('parser');
        $a = [];
        foreach ($parsers->getIterator() as $registeredParsers) {
            $a[] = $registeredParsers->textContent;
        }

        foreach ($extra['parser'] as $toBeRegistered) {
            if (in_array($toBeRegistered, $a, true)) {
                continue;
            }

            $parser = $xml->createElement('parser', $toBeRegistered);
            $root->appendChild($parser);
        }

        $validators = $root->getElementsByTagName('validator');
        $b = [];
        foreach ($validators->getIterator() as $registeredValidators) {
            $b[] = $registeredValidators->textContent;
        }

        foreach ($extra['validator'] as $toBeRegistered) {
            if (in_array($toBeRegistered, $b, true)) {
                continue;
            }

            $validator = $xml->createElement('validator', $toBeRegistered);
            $root->appendChild($validator);
        }

        $xml->save($xulietaConfigFile);

        $io->write('Updating file...');
    }

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
