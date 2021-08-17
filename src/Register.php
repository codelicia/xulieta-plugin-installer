<?php

declare(strict_types=1);

namespace Codelicia\Xulieta\AutoPlugin;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use DOMDocument;
use DOMElement;
use Symfony\Component\Config\Util\XmlUtils;

use function assert;
use function dirname;
use function file_exists;
use function in_array;
use function is_array;

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
 *
 * @internal
 */
final class Register implements PluginInterface, EventSubscriberInterface
{
    public static function scan(PackageEvent $event): void
    {
        if (! $event->isDevMode()) {
            return;
        }

        $operation = $event->getOperation();
        assert($operation instanceof InstallOperation);
        $package = $operation->getPackage();

        /** @var array<string,mixed> $packageExtra */
        $packageExtra = $package->getExtra();
        $extra        = self::getExtraMetadata($packageExtra);

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        self::injectModuleIntoConfig($extra, $event->getIO(), $event->getComposer());
    }

    /**
     * Retrieve the metadata from the "extra" section
     *
     * @param array{xulieta: array{parser: string, validator: string}} $extra
     *
     * @return array<string,mixed>
     */
    private static function getExtraMetadata(array $extra): array
    {
        $pluginConfiguration = [];

        if (isset($extra['xulieta']) && is_array($extra['xulieta'])) {
            /** @var array<string,mixed> $pluginConfiguration */
            $pluginConfiguration = $extra['xulieta'];
        }

        return $pluginConfiguration;
    }

    private static function injectModuleIntoConfig(array $extra, IOInterface $io, Composer $composer): void
    {
        $rootDir           = dirname($composer->getConfig()->getConfigSource()->getName());
        $xulietaConfigFile = $rootDir . '/xulieta.xml';

        // @todo create basic config file in case it doesn't exists?
        if (! file_exists($xulietaConfigFile)) {
            return;
        }

        // FIXME: Filter elements so it doesn't get repeated
        $xml  = XmlUtils::loadFile($xulietaConfigFile);
        $root = $xml->documentElement;

        $parsers = $root->getElementsByTagName('parser');
        $a       = [];
        foreach ($parsers->getIterator() as $registeredParsers) {
            assert($registeredParsers instanceof DOMElement);
            $a[] = $registeredParsers->textContent;
        }

        // @todo refactor to functional
        foreach ($extra['parser'] as $toBeRegistered) {
            if (in_array($toBeRegistered, $a, true)) {
                continue;
            }

            $registeredParsers?->parentNode?->insertBefore(
                $xml->createElement('parser', $toBeRegistered),
                $registeredParsers ?? null
            );
        }

        $validators = $root->getElementsByTagName('validator');
        $b          = [];

        foreach ($validators->getIterator() as $registeredValidators) {
            assert($registeredValidators instanceof DOMElement);
            $b[] = $registeredValidators->textContent;
        }

        // @todo poorly duplicated code
        foreach ($extra['validator'] as $toBeRegistered) {
            if (in_array($toBeRegistered, $b, true)) {
                continue;
            }

            $registeredValidators->parentNode->insertBefore(
                $xml->createElement('validator', $toBeRegistered),
                $registeredValidators ?? null
            );
        }

        // @fixme: workaround to save properly formatted xml
        $domxml                     = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput       = true;
        $domxml->loadXML($xml->saveXML());
        $domxml->save($xulietaConfigFile);

        $io->write('Updating file...');
    }

    /** @psalm-return array{post-package-install: string} */
    public static function getSubscribedEvents(): array
    {
        return [PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall'];
    }

    /** @return void */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Intentionally left blank
    }

    /** @return void */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Intentionally left blank
    }

    /** @return void */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Intentionally left blank
    }
}
