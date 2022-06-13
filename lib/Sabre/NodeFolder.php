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

use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;

class NodeFolder extends AbstractNode implements ICollection {
	/** @var Folder */
	protected Node $node;

	public function __construct(Folder $node, Folder $userFolder) {
		$this->node = $node;
		$this->userFolder = $userFolder;
	}

	public function getChildren(): array {
		return array_map(function (Node $entry) {
			return AbstractNode::new($entry, $this->userFolder);
		}, $this->node->getDirectoryListing());
	}

	public function getChild($name): AbstractNode {
		try {
			$node = $this->node->get($name);
		} catch (NotFoundException $e) {
			throw new NotFound($e->getMessage(), 0, $e);
		}

		return AbstractNode::new($node, $this->userFolder);
	}

	public function childExists($name): bool {
		try {
			$this->getChild($name);
			return true;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function createFile($name, $data = null) {
		$this->node->newFile($name, $data);
	}

	public function createDirectory($name) {
		$this->node->newFolder($name);
	}
}
