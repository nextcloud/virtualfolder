<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\VirtualFolder\Tests\Mount;

use OC\Files\Storage\Temporary;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCA\VirtualFolder\Folder\SourceFile;
use OCA\VirtualFolder\Folder\VirtualFolder;
use OCA\VirtualFolder\Folder\VirtualFolderFactory;
use OCA\VirtualFolder\Mount\VirtualFolderMount;
use OCA\VirtualFolder\Mount\VirtualFolderMountProvider;
use OCA\VirtualFolder\Mount\VirtualFolderRootMount;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class VirtualFolderMountProviderTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	/** @var IStorage */
	private $sourceStorage;
	/** @var FolderConfigManager */
	private $folderConfigManager;
	/** @var VirtualFolderFactory */
	private $folderFactory;
	/** @var VirtualFolderMountProvider */
	private $virtualMountProvider;

	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new Temporary([]);
		$this->createUser('source', 'source');
		$this->createUser('target', 'target');
		$this->registerMount('source', $this->sourceStorage, '/source/files/source');
		$this->folderConfigManager = $this->createMock(FolderConfigManager::class);
		$this->folderFactory = $this->createMock(VirtualFolderFactory::class);
		$this->virtualMountProvider = new VirtualFolderMountProvider($this->folderFactory, $this->folderConfigManager);
	}

	private function createSourceFile(string $name): SourceFile {
		$this->sourceStorage->file_put_contents($name, "$name content");
		$this->sourceStorage->getScanner()->scan($name);
		$cacheEntry = $this->sourceStorage->getCache()->get($name);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('source');
		return new SourceFile($cacheEntry, $this->sourceStorage->getId(), function () {
			return \OC::$server->get(IRootFolder::class);
		}, $user);
	}

	public function testGetMountsForFolder() {
		$folder = new VirtualFolder(1, [
			$this->createSourceFile("source1.txt"),
			$this->createSourceFile("source2.txt"),
			$this->createSourceFile("source3.txt"),
		], 'mp');

		$mounts = $this->virtualMountProvider->getMountsForFolder($folder, $this->storageFactory, '/target/files/virtual');
		$this->assertCount(4, $mounts);
		$this->assertInstanceOf(VirtualFolderRootMount::class, $mounts[0]);
		$this->assertInstanceOf(VirtualFolderMount::class, $mounts[1]);
		$this->assertInstanceOf(VirtualFolderMount::class, $mounts[2]);
		$this->assertInstanceOf(VirtualFolderMount::class, $mounts[3]);

		$this->assertEquals('/target/files/virtual/', $mounts[0]->getMountPoint());
		$this->assertEquals('/target/files/virtual/source1.txt/', $mounts[1]->getMountPoint());
		$this->assertEquals('/target/files/virtual/source2.txt/', $mounts[2]->getMountPoint());
		$this->assertEquals('/target/files/virtual/source3.txt/', $mounts[3]->getMountPoint());


		$this->assertEquals('source1.txt content', $mounts[1]->getStorage()->file_get_contents(''));
		$this->assertEquals('source2.txt content', $mounts[2]->getStorage()->file_get_contents(''));
		$this->assertEquals('source3.txt content', $mounts[3]->getStorage()->file_get_contents(''));
	}
}
