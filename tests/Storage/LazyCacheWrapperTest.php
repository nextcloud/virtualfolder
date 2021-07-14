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

namespace OCA\VirtualFolder\Tests\Storage;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Temporary;
use OCA\VirtualFolder\Storage\LazeCacheWrapper;
use Test\Files\Cache\CacheTest;

/**
 * @group DB
 */
class LazyCacheWrapperTest extends CacheTest {
	protected function setUp(): void {
		parent::setUp();

		$this->storage = new Temporary([]);
		$this->storage2 = new Temporary([]);
		$this->storage->getScanner('')->scan('');
		$this->storage2->getScanner('')->scan('');
		$cache = new Cache($this->storage);
		$cache2 = new Cache($this->storage2);
		$this->cache = new LazeCacheWrapper($cache->get(''), function () use ($cache) {
			return $cache;
		});
		$this->cache2 = new LazeCacheWrapper($cache2->get(''), function () use ($cache2) {
			return $cache2;
		});
	}

	/**
	 * Test bogus paths with leading or doubled slashes
	 *
	 * @dataProvider bogusPathNamesProvider
	 */
	public function testBogusPaths($bogusPath, $fixedBogusPath) {
		$data = ['size' => 100, 'mtime' => 50, 'mimetype' => 'httpd/unix-directory'];

		// changed from parent method, root item already exists
		$parentId = $this->cache->getId('');
		$this->assertGreaterThan(0, $parentId);

		$this->assertGreaterThan(0, $this->cache->put($bogusPath, $data));

		$newData = $this->cache->get($fixedBogusPath);
		$this->assertNotFalse($newData);

		$this->assertEquals($fixedBogusPath, $newData['path']);
		// parent is the correct one, resolved properly (they used to not be)
		$this->assertEquals($parentId, $newData['parent']);

		$newDataFromBogus = $this->cache->get($bogusPath);
		// same entry
		$this->assertEquals($newData, $newDataFromBogus);
	}

	public function testSearch() {
		$file1 = 'folder';
		$file2 = 'folder/foobar';
		$file3 = 'folder/foo';
		$data1 = ['size' => 100, 'mtime' => 50, 'mimetype' => 'foo/folder'];
		$fileData = [];
		$fileData['foobar'] = ['size' => 1000, 'mtime' => 20, 'mimetype' => 'foo/file'];
		$fileData['foo'] = ['size' => 20, 'mtime' => 25, 'mimetype' => 'foo/file'];

		$this->cache->put($file1, $data1);
		$this->cache->put($file2, $fileData['foobar']);
		$this->cache->put($file3, $fileData['foo']);

		$this->assertEquals(2, count($this->cache->search('%foo%')));
		$this->assertEquals(1, count($this->cache->search('foo')));
		$this->assertEquals(1, count($this->cache->search('%folder%')));
		$this->assertEquals(1, count($this->cache->search('folder%')));
		// changed from parent method, root item exists so 1 extra result
		$this->assertEquals(4, count($this->cache->search('%')));

		// case insensitive search should match the same files
		$this->assertEquals(2, count($this->cache->search('%Foo%')));
		$this->assertEquals(1, count($this->cache->search('Foo')));
		$this->assertEquals(1, count($this->cache->search('%Folder%')));
		$this->assertEquals(1, count($this->cache->search('Folder%')));

		$this->assertEquals(3, count($this->cache->searchByMime('foo')));
		$this->assertEquals(2, count($this->cache->searchByMime('foo/file')));
	}
}
