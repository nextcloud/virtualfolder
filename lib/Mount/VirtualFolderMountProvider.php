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

namespace OCA\VirtualFolder\Mount;

use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\Wrapper\Jail;
use OCA\VirtualFolder\Folder\SourceFile;
use OCA\VirtualFolder\Folder\VirtualFolder;
use OCA\VirtualFolder\Folder\VirtualFolderManager;
use OCA\VirtualFolder\Storage\EmptyStorage;
use OCA\VirtualFolder\Storage\LazyWrapper;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class VirtualFolderMountProvider implements IMountProvider {
	/** @var VirtualFolderManager */
	private $manager;

	public function __construct(VirtualFolderManager $manager) {
		$this->manager = $manager;
	}

	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
		$folders = $this->manager->getFoldersForUser($user);
		return array_merge(array_map(function(VirtualFolder $folder) use ($user, $loader) {
			return $this->getMountsForFolder($user, $folder, $loader);
		}, $folders));
	}

	/**
	 * @param IUser $user
	 * @param VirtualFolder $folder
	 * @param IStorageFactory $loader
	 * @return VirtualFolderMount[]
	 */
	private function getMountsForFolder(IUser $user, VirtualFolder $folder, IStorageFactory $loader): array {
		$baseMount = $folder->getMountPoint();
		$mounts = [
			new VirtualFolderMount(EmptyStorage::class, $baseMount, [], $loader)
		];

		foreach ($folder->getSourceFiles() as $sourceFile) {
			$mounts[] = new VirtualFolderMount($this->getStorageForSourceFile($sourceFile, $user), $baseMount . '/' . $sourceFile->getCacheEntry()->getName(), [], $loader);
		}

		return $mounts;
	}

	private function getStorageForSourceFile(SourceFile $sourceFile, IUser $user): LazyWrapper {
		return new LazyWrapper([
			'source_root_info' => $sourceFile->getCacheEntry(),
			'source_factory' => function () use ($sourceFile, $user) {
				return new Jail([
					'storage' => $sourceFile->getSourceStorage($user),
					'root' => $sourceFile->getCacheEntry()->getPath()
				]);
			},
			'storage_id' => $sourceFile->getSourceStorageId(),
		]);
	}
}
