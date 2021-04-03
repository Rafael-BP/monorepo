<?php

declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Package;

use Symplify\ComposerJsonManipulator\FileSystem\JsonFileManager;
use Symplify\MonorepoBuilder\FileSystem\ComposerJsonProvider;
use Symplify\MonorepoBuilder\ValueObject\Package;
use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\SymplifyKernel\Exception\ShouldNotHappenException;

final class PackageProvider
{
    /**
     * @var ComposerJsonProvider
     */
    private $composerJsonProvider;

    /**
     * @var JsonFileManager
     */
    private $jsonFileManager;

    public function __construct(ComposerJsonProvider $composerJsonProvider, JsonFileManager $jsonFileManager)
    {
        $this->composerJsonProvider = $composerJsonProvider;
        $this->jsonFileManager = $jsonFileManager;
    }

    /**
     * @return Package[]
     */
    public function provide(): array
    {
        $packages = [];
        foreach ($this->composerJsonProvider->getPackagesComposerFileInfos() as $packagesComposerFileInfo) {
            $packageName = $this->detectNameFromFileInfo($packagesComposerFileInfo);

            $hasTests = file_exists($packagesComposerFileInfo->getRealPathDirectory() . '/tests');
            $packages[] = new Package($packageName, $hasTests);
        }

        usort($packages, function (Package $firstPackage, Package $secondPackage): int {
            return $firstPackage->getShortName() <=> $secondPackage->getShortName();
        });

        return $packages;
    }

    private function detectNameFromFileInfo(SmartFileInfo $smartFileInfo): string
    {
        $json = $this->jsonFileManager->loadFromFileInfo($smartFileInfo);

        if (! isset($json['name'])) {
            throw new ShouldNotHappenException();
        }

        return (string) $json['name'];
    }
}
