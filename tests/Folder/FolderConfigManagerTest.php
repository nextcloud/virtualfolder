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

namespace OCA\VirtualFolder\Tests\Folder;

use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCA\VirtualFolder\Storage\EmptyStorage;
use OCP\Files\AlreadyExistsException;
use OCP\IDBConnection;
use Test\TestCase;

/**
 * @group DB
 */
class FolderConfigManagerTest extends TestCase {
	/** @var FolderConfigManager */
	private $configManager;

	protected function setUp(): void {
		parent::setUp();
		$this->configManager = new FolderConfigManager(\OC::$server->get(IDBConnection::class));
	}

	private function newFolder(string $userId, string $mountPoint, array $fileIds): FolderConfig {
		$createdFolder = $this->configManager->newFolder($userId, $mountPoint, $fileIds);
		$this->assertEquals($userId, $createdFolder->getUserId());
		$this->assertEquals($mountPoint, $createdFolder->getMountPoint());
		$this->assertEquals($fileIds, $createdFolder->getSourceFileIds());
		return $createdFolder;
	}

	public function testCreateGet() {
		$createdFolder = $this->newFolder('user1',  'create_get', [10, 20, 30, 40, 50]);

		$folders = $this->configManager->getFoldersForUser('user1');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder, $folders[0]);
	}

	public function testGetMultiple() {
		$createdFolder1 = $this->newFolder('multi1', 'get_multiple1', [10, 20, 30, 40, 50]);
		$createdFolder2 = $this->newFolder('multi1', 'get_multiple2', [11, 21, 31, 41, 51]);
		$createdFolder3 = $this->newFolder('multi2', 'get_multiple3', [12, 22, 32, 42, 52]);

		$folders = $this->configManager->getFoldersForUser('multi1');
		$this->assertCount(2, $folders);
		$this->assertEquals($createdFolder1, $folders[0]);
		$this->assertEquals($createdFolder2, $folders[1]);
	}

	public function testGetEmpty() {
		$createdFolder = $this->newFolder('empty', 'empty', []);

		$folders = $this->configManager->getFoldersForUser('empty');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder, $folders[0]);
	}

	public function testDelete() {
		$createdFolder1 = $this->newFolder('user4', 'delete', [10, 20, 30, 40, 50]);

		$folders = $this->configManager->getFoldersForUser('user4');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder1, $folders[0]);

		$this->configManager->deleteFolder($createdFolder1->getId());


		$folders = $this->configManager->getFoldersForUser('target4');
		$this->assertCount(0, $folders);
	}

	private function scanVirtualRoot(int $id): int {
		$storage = new EmptyStorage(['storage_id' => 'virtual_' . $id]);
		$storage->getScanner()->scan('');
		return $storage->getCache()->getId('');
	}

	public function testGetMultipleByRootId() {
		$createdFolder1 = $this->newFolder('user1', 'get_multiple1', [10, 20, 30, 40, 50]);
		$createdFolder2 = $this->newFolder('user2', 'get_multiple2', [11, 21, 31, 41, 51]);
		$createdFolder3 = $this->newFolder('user2', 'get_multiple3', [12, 22, 32, 42, 52]);
		$createdFolder4 = $this->newFolder('user2', 'get_multiple_empty', []);

		$rootId1 = $this->scanVirtualRoot($createdFolder1->getId());
		$rootId2 = $this->scanVirtualRoot($createdFolder2->getId());
		$rootId3 = $this->scanVirtualRoot($createdFolder3->getId());
		$rootId4 = $this->scanVirtualRoot($createdFolder4->getId());

		$folders = $this->configManager->getAllByRootIds();
		$this->assertCount(4, $folders);
		$this->assertEquals($createdFolder1, $folders[$rootId1]);
		$this->assertEquals($createdFolder2, $folders[$rootId2]);
		$this->assertEquals($createdFolder3, $folders[$rootId3]);
		$this->assertEquals($createdFolder4, $folders[$rootId4]);
	}

	public function testSetMountPoint() {
		$createdFolder1 = $this->newFolder('user5', 'source', [10, 20, 30, 40, 50]);

		$folders = $this->configManager->getFoldersForUser('user5');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder1, $folders[0]);

		$this->configManager->setMountPoint($createdFolder1->getId(), 'target');
		$folders = $this->configManager->getFoldersForUser('user5');
		$this->assertCount(1, $folders);
		$this->assertEquals('target', $folders[0]->getMountPoint());
	}

	public function testCreateDuplicate() {
		$this->newFolder('duplicate1', 'duplicate', [10, 20, 30, 40, 50]);
		$this->expectException(AlreadyExistsException::class);
		$this->newFolder('duplicate1', 'duplicate', [11, 21, 31, 41, 51]);
	}
}
