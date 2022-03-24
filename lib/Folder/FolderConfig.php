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

class FolderConfig {
	private int $id;
	private string $userId;
	private string $mountPoint;
	private array $sourceFileIds;

	public function __construct(int $id, string $userId, string $mountPoint, array $sourceFileIds) {
		$this->id = $id;
		$this->userId = $userId;
		$this->mountPoint = $mountPoint;
		$this->sourceFileIds = $sourceFileIds;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getMountPoint(): string {
		return $this->mountPoint;
	}

	/**
	 * @return int[]
	 */
	public function getSourceFileIds(): array {
		return $this->sourceFileIds;
	}
}
