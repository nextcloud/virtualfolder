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

use OC\Files\Filesystem;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File as SabreFile;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\File;
use OCP\Files\Node;
use Sabre\DAV\INode;

abstract class AbstractNode implements INode {
	protected Node $node;
	protected Folder $userFolder;

	public function getNode(): Node {
		return $this->node;
	}

	public function getSource(): INode {
		$view = Filesystem::getView();
		if ($this->node->getMimeType() === FileInfo::MIMETYPE_FOLDER) {
			return new Directory($view, $this->node);
		} else {
			return new SabreFile($view, $this->node);
		}
	}

	/**
	 * Deleted the current node.
	 */
	public function delete() {
		$this->node->delete();
	}

	public function getName() {
		return $this->node->getName();
	}

	public function setName($name) {
		$this->node->move($this->node->getParent()->getPath() . '/' . $name);
	}

	public function getLastModified() {
		return $this->node->getMTime();
	}

	public static function new(Node $node, Folder $userFolder) {
		if ($node instanceof Folder) {
			return new NodeFolder($node, $userFolder);
		} elseif ($node instanceof File) {
			return new NodeFile($node, $userFolder);
		} else {
			throw new \Exception("Invalid node, neither file nor folder");
		}
	}

	public static function newTopLevel(Node $node, FolderConfig $folder, FolderConfigManager $folderConfigManager, Folder $userFolder) {
		if ($node instanceof Folder) {
			return new TopLevelNodeFolder($node, $folder, $folderConfigManager, $userFolder);
		} elseif ($node instanceof File) {
			return new TopLevelNodeFile($node, $folder, $folderConfigManager, $userFolder);
		} else {
			throw new \Exception("Invalid node, neither file nor folder");
		}
	}

	public function getPath(): string {
		return $this->userFolder->getRelativePath($this->getNode()->getPath());
	}
}
