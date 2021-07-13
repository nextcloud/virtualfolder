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

namespace OCA\VirtualFolder\Folder;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

class SourceFile {
	/** @var ICacheEntry */
	public $cacheEntry;
	/** @var string */
	public $storageId;
	/** @var IRootFolder */
	public $rootFolder;

	public function getCacheEntry(): ICacheEntry {
		return $this->cacheEntry;
	}

	public function getSourceStorage(IUser $user): IStorage {
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($this->cacheEntry->getId());
		if ($node = current($nodes)) {
			/** @var Node $node */
			return $node->getStorage();
		} else {
			return throw new NotFoundException("Source file for virtual folder not found");
		}
	}

	public function getSourceStorageId(): string{
		return $this->storageId;
	}
}
