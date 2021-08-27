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

namespace OCA\VirtualFolder\Storage;

use Icewind\Streams\IteratorDirectory;
use OC\Files\Storage\Common;
use OCP\Constants;

class EmptyStorage extends Common {
	/** @var string */
	private $storageId;

	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->storageId = $parameters['storage_id'] ?? 'empty';
	}

	public function getId() {
		return $this->storageId;
	}

	public function mkdir($path) {
		return false;
	}

	public function rmdir($path) {
		return false;
	}

	public function opendir($path) {
		return IteratorDirectory::wrap([]);
	}

	public function stat($path) {
		return [
			'size' => 0,
			'mtime' => time(),
		];
	}

	public function filetype($path) {
		return $path === '' ? 'dir' : false;
	}

	public function file_exists($path) {
		return $path === '';
	}

	public function unlink($path) {
		return false;
	}

	public function fopen($path, $mode) {
		return false;
	}

	public function touch($path, $mtime = null) {
		return false;
	}

	public function getPermissions($path) {
		return Constants::PERMISSION_READ + Constants::PERMISSION_SHARE + Constants::PERMISSION_UPDATE;
	}

	public function hasUpdated($path, $time) {
		return false;
	}
}
