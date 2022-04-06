<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

namespace OCA\VirtualFolder\Command;


use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCP\Files\IRootFolder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Command {
	protected FolderConfigManager $configManager;
	private IRootFolder $rootFolder;

	public function __construct(FolderConfigManager $configManager, IRootFolder $rootFolder) {
		parent::__construct();

		$this->configManager = $configManager;
		$this->rootFolder = $rootFolder;
	}

	protected function configure() {
		$this
			->setName('virtualfolder:add')
			->setDescription('Add files to a virtual folder')
			->addArgument(
				'folder_id',
				InputArgument::REQUIRED,
				'Id of the virtual folder'
			)
			->addArgument(
				'file_ids',
				InputArgument::IS_ARRAY,
				'File ids to add to the folder'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$folderId = (int)$input->getArgument('folder_id');
		$folder = $this->configManager->getById($folderId);
		if (!$folder) {
			$output->writeln("Folder with id $folderId not found");
			return 1;
		}
		$userId = $folder->getUserId();

		$fileIds = $input->getArgument('file_ids');

		$userFolder = $this->rootFolder->getUserFolder($userId);

		foreach ($fileIds as $fileId) {
			$id = (int)$fileId;
			if (in_array($id, $folder->getSourceFileIds())) {
				$output->writeln("<error>File $id already in folder, skipping</error>");
			} else {
				$nodes = $userFolder->getById($id);
				if ($nodes) {
					$this->configManager->addSourceFile($folderId, $id);
				} else {
					$output->writeln("<error>No file with id $id found for $userId, skipping</error>");
				}
			}
		}

		return 0;
	}
}
