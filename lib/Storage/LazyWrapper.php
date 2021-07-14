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

use OC\Files\Cache\FailedCache;
use OC\Files\Cache\Storage;
use OC\Files\ObjectStore\HomeObjectStoreStorage;
use OC\Files\Storage\Common;
use OC\Files\Storage\FailedStorage;
use OC\Files\Storage\Home;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\User\NoUserException;
use OCP\Files\Cache\ICache;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IHomeStorage;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;

/**
 * Cache wrapper that doesn't require the source storage to be setup in advance
 */
class LazyWrapper extends Wrapper {
	private $initialized = false;

	/** @var ICacheEntry */
	private $sourceRootInfo;

	/** @var callable */
	private $sourceFactory;

	/** @var ICache|null */
	public $cache = null;

	/** @var IStorage|null */
	public $storage = null;

	/** @var string */
	private $storageId;

	/** @var Storage|null */
	private $storageCache = null;

	public function __construct($arguments) {
		$this->sourceRootInfo = $arguments['source_root_info'];
		$this->sourceFactory = $arguments['source_factory'];
		$this->storageId = $arguments['storage_id'];
		$this->cache = new LazeCacheWrapper($this->sourceRootInfo, function () {
			return $this->getWrapperStorage()->getCache();
		});
	}

	private function init() {
		if (!$this->initialized) {
			$this->initialized = true;
			try {
				$this->storage = ($this->sourceFactory)();
			} catch (NotFoundException $e) {
				// original file not accessible or deleted, set FailedStorage
				$this->storage = new FailedStorage(['exception' => $e]);
				$this->cache = new FailedCache();
			} catch (NoUserException $e) {
				// sharer user deleted, set FailedStorage
				$this->storage = new FailedStorage(['exception' => $e]);
				$this->cache = new FailedCache();
			} catch (\Exception $e) {
				$this->storage = new FailedStorage(['exception' => $e]);
				$this->cache = new FailedCache();
			}
		}
	}

	public function getId(): string {
		return $this->storageId;
	}

	public function getCache($path = '', $storage = null) {
		return $this->cache;
	}

	public function getStorageCache($storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->storageCache)) {
			$this->storageCache = new Storage($storage);
		}
		return $this->storageCache;
	}

	public function getWrapperStorage() {
		$this->init();
		return $this->storage;
	}

	public function instanceOfStorage($class) {
		$class = ltrim($class, '\\');
		if ($class === Common::class) {
			return true;
		}
		if ($class === Home::class || $class === HomeObjectStoreStorage::class || $class === IHomeStorage::class) {
			return false;
		}
		if ($class === "OCA\Files_Sharing\SharedStorage") {
			return false;
		}
		return parent::instanceOfStorage($class);
	}

	public function setMountOptions($options) {
		if ($options) {
			parent::setMountOptions($options);
		}
	}

	public function isLocal() {
		if (strpos($this->getId(), "home::") === 0 || strpos($this->getId(), "local::") === 0) {
			return true;
		} else {
			return false;
		}
	}
}
