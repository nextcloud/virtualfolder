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

use OCA\DAV\Connector\Sabre\Node as DavNode;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\ICopyTarget;
use Sabre\DAV\INode;

class FolderRoot implements ICollection, ICopyTarget {
	/** @var FolderConfigManager */
	private $configManager;
	/** @var FolderConfig */
	private $folder;
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(FolderConfigManager $configManager, FolderConfig $folder, IRootFolder $rootFolder) {
		$this->configManager = $configManager;
		$this->folder = $folder;
		$this->rootFolder = $rootFolder;
	}

	public function delete() {
		$this->configManager->deleteFolder($this->folder->getId());
	}

	public function getName(): string {
		return basename($this->folder->getMountPoint());
	}

	public function setName($name) {
		// todo, maybe allow this?
		throw new Forbidden('Permission denied to rename this folder');
	}

	public function createFile($name, $data = null) {
		throw new Forbidden('Not allowed to create files in this folder, copy files into this folder instead');
	}

	public function createDirectory($name) {
		throw new Forbidden('Not allowed to create directories in this folder, copy directories into this folder instead');
	}

	public function getChildren(): array {
		$node = $this->rootFolder->get($this->folder->getMountPoint());

		return array_map(function (Node $entry) {
			return AbstractNode::newTopLevel($entry, $this->configManager);
		}, $node->getDirectoryListing());
	}

	public function getChild($name): AbstractNode {
		$node = $this->rootFolder->get($this->folder->getMountPoint());
		try {
			$node = $node->get($name);
		} catch (NotFoundException $e) {
			throw new NotFound($e->getMessage(), 0, $e);
		}

		return AbstractNode::newTopLevel($node, $this->configManager);
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

	public function copyInto($targetName, $sourcePath, INode $sourceNode): bool {
		$uid = $this->folder->getSourceUserId();
		if ($sourceNode instanceof DavNode) {
			$sourceId = $sourceNode->getId();
			if ($sourceNode->getFileInfo()->getOwner()->getUID() === $uid) {
				$this->configManager->addSourceFile($this->folder->getId(), $sourceId);
				return true;
			}
		}
		throw new \Exception("Can't add file to virtual folder, only files from $uid can be added");
	}
}
