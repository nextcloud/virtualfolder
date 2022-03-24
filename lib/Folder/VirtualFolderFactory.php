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

use OC\Files\Cache\CacheEntry;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Container\ContainerInterface;

class VirtualFolderFactory {
	private IDBConnection $connection;
	private ContainerInterface $rootFolderContainer;
	private IUserManager $userManager;
	private IMimeTypeLoader $mimeTypeLoader;

	public function __construct(
		IDBConnection $connection,
		ContainerInterface $rootFolderContainer,
		IUserManager $userManager,
		IMimeTypeLoader $mimeTypeLoader
	) {
		$this->connection = $connection;
		$this->rootFolderContainer = $rootFolderContainer;
		$this->userManager = $userManager;
		$this->mimeTypeLoader = $mimeTypeLoader;
	}

	/**
	 * @param FolderConfig[] $folders
	 * @return VirtualFolder[]
	 */
	public function createFolders(array $folders): array {
		$rootFolderFactory = function () {
			return $this->rootFolderContainer->get(IRootFolder::class);
		};
		return array_map(function (FolderConfig $folder) use ($rootFolderFactory) {
			$user = $this->userManager->get($folder->getUserId());
			if ($user === null) {
				throw new NotFoundException("Source user not found for virtual folder");
			}
			$sourceFiles = $this->getSourceFilesFromFileIds($user, $rootFolderFactory, $folder->getSourceFileIds());
			usort($sourceFiles, function (SourceFile $a, SourceFile $b) {
				return $a->getCacheEntry()->getId() <=> $b->getCacheEntry()->getId();
			});
			return new VirtualFolder($folder->getId(), $sourceFiles, $folder->getMountPoint());
		}, $folders);
	}

	private function getSourceFilesFromFileIds(IUser $user, callable $rootFolderFactory, array $sourceFileIds): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid', 'storage', 'path', 'parent', 'name', 'mimetype', 'mimepart', 'size', 'mtime', 'storage_mtime', 'encrypted', 'unencrypted_size', 'etag', 'permissions', 'checksum', 'id')
			->from('filecache', 'f')
			->innerJoin('f', 'storages', 's', $query->expr()->eq('storage', 'numeric_id'))
			->where($query->expr()->in('fileid', $query->createNamedParameter($sourceFileIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$results = $query->execute()->fetchAll();
		return array_map(function (array $row) use ($rootFolderFactory, $user) {
			$row['mimetype'] = $this->mimeTypeLoader->getMimetypeById($row['mimetype']);
			$row['mimepart'] = $this->mimeTypeLoader->getMimetypeById($row['mimepart']);
			$cacheEntry = new CacheEntry($row);
			return new SourceFile($cacheEntry, $row['id'], $rootFolderFactory, $user);
		}, $results);
	}
}
