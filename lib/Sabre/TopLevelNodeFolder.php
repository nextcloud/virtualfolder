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

use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCP\Files\Folder;

class TopLevelNodeFolder extends NodeFolder {
	private FolderConfigManager $configManager;
	private FolderConfig $folder;

	public function __construct(Folder $node, FolderConfig $folder, FolderConfigManager $configManager) {
		parent::__construct($node);

		$this->folder = $folder;
		$this->configManager = $configManager;
	}

	public function delete() {
		$this->configManager->removeSourceFile($this->folder->getId(), $this->node->getId());
	}
}
