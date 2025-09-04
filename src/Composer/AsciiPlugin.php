<?php

namespace Conrado\LaravelBasicToolsForAI\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;

final class AsciiPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var IOInterface */
    private $io;

    /** @var string */
    private $packageName = 'conrado/laravel-basic-ai-tools';

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
        ];
    }

    public function onPostPackageInstall($event): void
    {
        $operation = $event->getOperation();
        if (!$operation instanceof InstallOperation) {
            return;
        }

        $package = $operation->getPackage();
        if ($this->isOurPackage($package)) {
            $this->printAscii();
        }
    }

    private function isOurPackage(PackageInterface $package): bool
    {
        return strtolower($package->getName()) === $this->packageName;
    }

    private function printAscii(): void
    {
        // Be polite in non-interactive CI runs
        if (method_exists($this->io, 'isInteractive') && !$this->io->isInteractive()) {
            return;
        }

        $ascii = <<<'ASCII'
  /$$$$$$                                               /$$          
 /$$__  $$                                             | $$          
| $$  \__/  /$$$$$$  /$$$$$$$   /$$$$$$  /$$$$$$   /$$$$$$$  /$$$$$$ 
| $$       /$$__  $$| $$__  $$ /$$__  $$|____  $$ /$$__  $$ /$$__  $$
| $$      | $$  \ $$| $$  \ $$| $$  \__/ /$$$$$$$| $$  | $$| $$  \ $$
| $$    $$| $$  | $$| $$  | $$| $$      /$$__  $$| $$  | $$| $$  | $$
|  $$$$$$/|  $$$$$$/| $$  | $$| $$     |  $$$$$$$|  $$$$$$$|  $$$$$$/
 \______/  \______/ |__/  |__/|__/      \_______/ \_______/ \______/

ASCII;

        $this->io->write($ascii);
        $this->io->write('<info>Basic tools for Laravel using the OpenAI API by Conrado</info>');
    }
}