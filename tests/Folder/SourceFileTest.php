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

use OC\Files\Storage\Temporary;
use OCA\VirtualFolder\Folder\SourceFile;
use OCP\Files\IRootFolder;
use OCP\IUser;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class SourceFileTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	public function testGetSourceStorage() {
		$this->createUser("source_file_test", "");
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn("source_file_test");

		$storage = new Temporary([]);
		$storage->mkdir("sub");
		$storage->file_put_contents("sub/foo", "bar");
		$storage->getScanner()->scan("");
		$cacheEntry = $storage->getCache()->get("sub/foo");

		$this->registerMount("source_file_test", $storage, "source_file_test/files/test");
		$this->loginAsUser("source_file_test");

		$sourceFile = new SourceFile($cacheEntry, $storage->getId(), function () {
			return \OC::$server->get(IRootFolder::class);
		}, $user);

		$this->assertEquals($storage, $sourceFile->getSourceStorage());
	}
}
