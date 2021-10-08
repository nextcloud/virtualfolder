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
namespace OCA\VirtualFolder\Sabre;

use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCA\VirtualFolder\Folder\VirtualFolderFactory;
use OCA\VirtualFolder\Mount\VirtualFolderMountProvider;
use OCP\Files\IRootFolder;
use OCP\IUser;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class VirtualFolderHome implements ICollection {
	/** @var FolderConfigManager */
	private $configManager;

	/** @var array */
	private $principalInfo;

	/** @var IUser */
	private $user;

	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(
		array $principalInfo,
		FolderConfigManager $configManager,
		IUser $user,
		IRootFolder $rootFolder
	) {
		$this->principalInfo = $principalInfo;
		$this->configManager = $configManager;
		$this->user = $user;
		$this->rootFolder = $rootFolder;
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName(): string {
		[, $name] = \Sabre\Uri\split($this->principalInfo['uri']);
		return $name;
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Not allowed to create files in this folder');
	}

	public function createDirectory($name) {
		$uid = $this->user->getUID();
		$this->configManager->newFolder($uid, $uid, "/$uid/virtualfolder/$name", []);
	}

	public function getChild($name) {
		foreach ($this->getChildren() as $child) {
			if ($child->getName() === $name) {
				return $child;
			}
		}

		throw new NotFound();
	}

	/**
	 * @return FolderRoot[]
	 */
	public function getChildren(): array {
		$folders = $this->configManager->getFoldersForUser($this->user->getUID());
		return array_map(function(FolderConfig $folder) {
			return new FolderRoot($this->configManager, $folder, $this->rootFolder);
		}, $folders);
	}

	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function getLastModified(): int {
		return 0;
	}
}
