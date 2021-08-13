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

namespace OCA\VirtualFolder\Listeners;

use OCA\Files_Sharing\Event\ShareMountedEvent;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCA\VirtualFolder\Folder\VirtualFolder;
use OCA\VirtualFolder\Folder\VirtualFolderFactory;
use OCA\VirtualFolder\Mount\VirtualFolderMountProvider;
use OCA\VirtualFolder\Mount\VirtualFolderRootMount;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;

class ShareMountedListener implements IEventListener {
	/** @var array<int, FolderConfig> */
	private $virtualFoldersByRoot;
	/** @var IStorageFactory */
	private $storageFactory;
	/** @var VirtualFolderFactory */
	private $folderManager;
	/** @var VirtualFolderMountProvider */
	private $mountProvider;

	public function __construct(FolderConfigManager $folderConfigManager, IStorageFactory $storageFactory, VirtualFolderFactory $folderManager, VirtualFolderMountProvider $mountProvider) {
		$this->virtualFoldersByRoot = $folderConfigManager->getAllByRootIds();
		$this->storageFactory = $storageFactory;
		$this->folderManager = $folderManager;
		$this->mountProvider = $mountProvider;
	}

	public function handle(Event $event): void {
		// add the individual file mounts if a virtual folder root is mounted through a share
		if ($event instanceof ShareMountedEvent) {
			$baseMount = $event->getMount();
			$share = $baseMount->getShare();
			$rootId = $share->getNodeCacheEntry()->getId();
			$folderConfig = $this->virtualFoldersByRoot[$rootId] ?? null;
			if ($folderConfig) {
				/** @var VirtualFolder $folder */
				$folder = current($this->folderManager->createFolders([$folderConfig]));
				$mounts = $this->mountProvider->getMountsForFolder($folder, $this->storageFactory, trim($baseMount->getMountPoint(), '/'), $share->getPermissions());

				// the root folder is already mounted by the share itself
				$mounts = array_filter($mounts, function (IMountPoint $mountPoint) {
					return !$mountPoint instanceof VirtualFolderRootMount;
				});
				foreach ($mounts as $mount) {
					$event->addMount($mount);
				}
			}
		}
	}
}
