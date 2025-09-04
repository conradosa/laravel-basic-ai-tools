<?php

namespace Conrado\LaravelBasicToolsForAI\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\ScriptEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

final class AsciiPlugin implements PluginInterface, EventSubscriberInterface
{
    private IOInterface $io;
    private string $packageName = 'conrado/laravel-basic-ai-tools';

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
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallOrUpdate',
            ScriptEvents::POST_UPDATE_CMD  => 'onPostInstallOrUpdate',
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
            $this->printAsciiOnce($event->getComposer());
        }
    }

    public function onPostInstallOrUpdate(Event $event): void
    {
        $this->printAsciiOnce($event->getComposer());
    }

    private function isOurPackage(PackageInterface $package): bool
    {
        return strtolower($package->getName()) === $this->packageName;
    }

    private function printAsciiOnce(Composer $composer): void
    {
        if (method_exists($this->io, 'isInteractive') && !$this->io->isInteractive()) {
            return;
        }

        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $pkgDir = $vendorDir . '/conrado/laravel-basic-ai-tools';
        if (!is_dir($pkgDir)) {
            return;
        }

        $marker = $pkgDir . '/.banner_shown';
        if (file_exists($marker)) {
            return;
        }

        $this->printAscii();

        @file_put_contents($marker, "shown\n");
    }

    private function printAscii(): void
    {
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
