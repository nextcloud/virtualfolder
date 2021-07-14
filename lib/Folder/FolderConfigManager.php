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

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class FolderConfigManager {
	/** @var IDBConnection */
	private $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param string $targetUserId
	 * @return FolderConfig[]
	 */
	public function getFoldersForUser(string $targetUserId): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('folder.folder_id', 'source_user', 'target_user', 'mount_point', 'file_id')
			->from('virtual_folders', 'folder')
			->innerJoin('folder', 'virtual_folder_files', 'files', $query->expr()->eq('folder.folder_id', 'files.folder_id'))
			->where($query->expr()->eq('target_user', $query->createNamedParameter($targetUserId)));
		$rows = $query->executeQuery()->fetchAll();

		return $this->fromRows($rows);
	}

	/**
	 * @return FolderConfig[]
	 */
	public function getAllFolders(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('folder.folder_id', 'source_user', 'target_user', 'mount_point', 'file_id')
			->from('virtual_folders', 'folder')
			->innerJoin('folder', 'virtual_folder_files', 'files', $query->expr()->eq('folder.folder_id', 'files.folder_id'));
		$rows = $query->executeQuery()->fetchAll();

		return $this->fromRows($rows);
	}

	public function deleteFolder(int $id) {
		$query = $this->connection->getQueryBuilder();
		$query->delete('virtual_folder_files')
			->where($query->expr()->eq('folder_id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$query = $this->connection->getQueryBuilder();
		$query->delete('virtual_folders')
			->where($query->expr()->eq('folder_id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * @param string $sourceUserId
	 * @param string $targetUserId
	 * @param int[] $fileIds
	 * @return FolderConfig
	 */
	public function newFolder(string $sourceUserId, string $targetUserId, string $mountPoint, array $fileIds): FolderConfig {
		$query = $this->connection->getQueryBuilder();
		$query->insert('virtual_folders')
			->values([
				'source_user' => $query->createNamedParameter($sourceUserId),
				'target_user' => $query->createNamedParameter($targetUserId),
				'mount_point' => $query->createNamedParameter($mountPoint),
			]);
		$query->executeStatement();
		$folderId = $query->getLastInsertId();

		foreach ($fileIds as $fileId) {
			$query = $this->connection->getQueryBuilder();
			$query->insert('virtual_folder_files')
				->values([
					'folder_id' => $query->createNamedParameter($folderId, IQueryBuilder::PARAM_INT),
					'file_id' => $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT),
				]);
			$query->executeStatement();
		}

		return new FolderConfig($folderId, $sourceUserId, $targetUserId, $mountPoint, $fileIds);
	}

	/**
	 * @param array $rows
	 * @return FolderConfig[]
	 */
	public function fromRows(array $rows): array {
		$folders = [];

		foreach ($rows as $row) {
			$folderId = $row['folder_id'];
			if (!isset($folders[$folderId])) {
				$folders[$folderId] = [
					'id' => (int)$folderId,
					'source_user' => $row['source_user'],
					'target_user' => $row['target_user'],
					'mount_point' => $row['mount_point'],
					'files' => [],
				];
			}
			$folders[$folderId]['files'][] = (int)$row['file_id'];
		}

		ksort($folders);

		return array_map(function (array $folder) {
			return new FolderConfig($folder['id'], $folder['source_user'], $folder['target_user'], $folder['mount_point'], $folder['files']);
		}, array_values($folders));
	}
}
