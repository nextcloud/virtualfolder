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

use OC\Files\Cache\Wrapper\CacheWrapper;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;

class LazeCacheWrapper extends CacheWrapper {
	/** @var callable */
	private $cacheFactory;
	private ICacheEntry $sourceRootInfo;
	private bool $rootUnchanged = true;
	private int $numericId;

	public function __construct(ICacheEntry $sourceRootInfo, callable $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
		$this->sourceRootInfo = $sourceRootInfo;
		$this->numericId = (int)$sourceRootInfo->getStorageId();

		parent::__construct(null);
	}

	public function getCache() {
		if (is_null($this->cache)) {
			$this->cache = ($this->cacheFactory)();
		}
		return $this->cache;
	}

	public function getNumericStorageId() {
		if (isset($this->numericId)) {
			return $this->numericId;
		} else {
			return false;
		}
	}

	public function getId($file) {
		if ($this->rootUnchanged && ($file === '' || $file === $this->sourceRootInfo->getId())) {
			return $this->sourceRootInfo->getId();
		}
		return parent::getId($file);
	}

	public function get($file) {
		if ($this->rootUnchanged && ($file === '' || $file === $this->sourceRootInfo->getId())) {
			return $this->formatCacheEntry(clone $this->sourceRootInfo);
		}
		return parent::get($file);
	}

	public function clear() {
		$this->rootUnchanged = false;
		parent::clear();
	}

	public function update($id, array $data) {
		$this->rootUnchanged = false;
		parent::update($id, $data);
	}

	public function insert($file, array $data): int {
		$this->rootUnchanged = false;
		return parent::insert($file, $data);
	}

	public function remove($file) {
		$this->rootUnchanged = false;
		parent::remove($file);
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		$this->rootUnchanged = false;
		parent::moveFromCache($sourceCache, $sourcePath, $targetPath);
	}
}
