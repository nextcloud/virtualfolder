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

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OCA\VirtualFolder\Folder\FolderConfigManager;

class VirtualFolderRootMount extends MountPoint implements MoveableMount {
	/** @var FolderConfigManager */
	private $folderConfig;
	/** @var int */
	private $folderId;

	public function __construct(
		FolderConfigManager $folderConfig,
		int                 $folderId,
							$storage,
							$mountpoint,
							$arguments = null,
							$loader = null
	) {
		parent::__construct($storage, $mountpoint, $arguments, $loader);
		$this->folderConfig = $folderConfig;
		$this->folderId = $folderId;
	}

	/**
	 * @return string
	 */
	public function getMountType() {
		return 'virtual';
	}

	public function moveMount($target) {
		$relativeTarget = $this->stripUserFilesPath($target);
		if ($relativeTarget) {
			$this->setMountPoint($target);
			$this->folderConfig->setMountPoint($this->folderId, $relativeTarget);
			return true;
		} else {
			return false;
		}
	}

	public function removeMount() {
		$this->folderConfig->deleteFolder($this->folderId);
		return true;
	}

	protected function stripUserFilesPath(string $path): ?string {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed, 3);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			return null;
		}

		return $split[2];
	}
}
