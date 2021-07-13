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

namespace OCA\VirtualFolder\Tests\Storage;

use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\Temporary;
use OCA\VirtualFolder\Storage\LazyWrapper;
use Test\Files\Storage\Storage;

/**
 * @group DB
 */
class LazyWrapperTest extends Storage {
	protected function setUp(): void {
		parent::setUp();

		$inner = new Temporary([]);
		$this->instance = new LazyWrapper([
			'source_root_info' => new CacheEntry(['storage' => -1]),
			'source_factory' => function () use ($inner) {
				return $inner;
			},
			'storage_id' => $inner->getId(),
		]);
	}
}
