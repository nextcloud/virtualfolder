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

	private function newFolder(string $sourceUserId, string $targetUserId, string $mountPoint, array $fileIds): FolderConfig {
		$createdFolder = $this->configManager->newFolder($sourceUserId, $targetUserId,$mountPoint, $fileIds);
		$this->assertEquals($sourceUserId, $createdFolder->getSourceUserId());
		$this->assertEquals($targetUserId, $createdFolder->getTargetUserId());
		$this->assertEquals($mountPoint, $createdFolder->getMountPoint());
		$this->assertEquals($fileIds, $createdFolder->getSourceFileIds());
		return $createdFolder;
	}

	public function testCreateGet() {
		$createdFolder = $this->newFolder('source1', 'target1','create_get', [10, 20, 30, 40, 50]);

		$folders = $this->configManager->getFoldersForUser('target1');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder, $folders[0]);
	}

	public function testGetMultiple() {
		$createdFolder1 = $this->newFolder('source1', 'target2','get_multiple1', [10, 20, 30, 40, 50]);
		$createdFolder2 = $this->newFolder('source2', 'target2','get_multiple2', [11, 21, 31, 41, 51]);
		$createdFolder3 = $this->newFolder('source2', 'target3','get_multiple3', [12, 22, 32, 42, 52]);

		$folders = $this->configManager->getFoldersForUser('target2');
		$this->assertCount(2, $folders);
		$this->assertEquals($createdFolder1, $folders[0]);
		$this->assertEquals($createdFolder2, $folders[1]);
	}

	public function testDelete() {
		$createdFolder1 = $this->newFolder('source1', 'target4','delete', [10, 20, 30, 40, 50]);

		$folders = $this->configManager->getFoldersForUser('target4');
		$this->assertCount(1, $folders);
		$this->assertEquals($createdFolder1, $folders[0]);

		$this->configManager->deleteFolder($createdFolder1->getId());


		$folders = $this->configManager->getFoldersForUser('target4');
		$this->assertCount(0, $folders);
	}
}
