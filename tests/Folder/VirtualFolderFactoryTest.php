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

use OC\AppFramework\Utility\SimpleContainer;
use OC\Files\Storage\Temporary;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\VirtualFolderFactory;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

/**
 * @group DB
 */
class VirtualFolderFactoryTest extends TestCase {
	/** @var VirtualFolderFactory */
	private $factory;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var IStorage */
	private $storage;
	/** @var \OCP\Files\Cache\ICache */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->userManager->method('get')
			->willReturnCallback(function ($uid) {
				$user = $this->createMock(IUser::class);
				$user->method('getUID')
					->willReturn($uid);
				return $user;
			});

		$container = new SimpleContainer();
		$this->factory = new VirtualFolderFactory(\OC::$server->get(IDBConnection::class), $container, $this->userManager, \OC::$server->get(IMimeTypeLoader::class));
		$this->storage = new Temporary([]);
		$this->cache = $this->storage->getCache();
	}

	public function testSingleFileFolder() {
		$id1 = $this->cache->insert('foo1', [
			'mtime' => 1,
			'size' => 1,
			'mimetype' => 'text/plain',
		]);

		$config = new FolderConfig(1, "user1", "foo", [$id1]);
		$folders = $this->factory->createFolders([$config]);
		$this->assertCount(1, $folders);
		$this->assertCount(1, $folders[0]->getSourceFiles());
		$this->assertEquals($id1, $folders[0]->getSourceFiles()[0]->getCacheEntry()->getId());
		$this->assertEquals('foo1', $folders[0]->getSourceFiles()[0]->getCacheEntry()->getPath());
		$this->assertEquals('text/plain', $folders[0]->getSourceFiles()[0]->getCacheEntry()->getMimeType());
	}

	public function testMultipleFolders() {
		$id1 = $this->cache->insert('foo2', [
			'mtime' => 1,
			'size' => 1,
			'mimetype' => 'text/plain',
		]);
		$id2 = $this->cache->insert('foo3', [
			'mtime' => 2,
			'size' => 2,
			'mimetype' => 'text/plain',
		]);
		$id3 = $this->cache->insert('foo4', [
			'mtime' => 3,
			'size' => 3,
			'mimetype' => 'text/plain',
		]);

		$config1 = new FolderConfig(1, "user1", "foo", [$id1, $id2]);
		$config2 = new FolderConfig(2, "user2", "foo", [$id2, $id3]);
		$folders = $this->factory->createFolders([$config1, $config2]);
		$this->assertCount(2, $folders);
		$this->assertCount(2, $folders[0]->getSourceFiles());
		$this->assertEquals($id1, $folders[0]->getSourceFiles()[0]->getCacheEntry()->getId());
		$this->assertEquals($id2, $folders[0]->getSourceFiles()[1]->getCacheEntry()->getId());
		$this->assertCount(2, $folders[1]->getSourceFiles());
		$this->assertEquals($id2, $folders[1]->getSourceFiles()[0]->getCacheEntry()->getId());
		$this->assertEquals($id3, $folders[1]->getSourceFiles()[1]->getCacheEntry()->getId());
	}
}
