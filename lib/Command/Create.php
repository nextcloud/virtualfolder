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

namespace OCA\VirtualFolder\Command;

use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command {
	protected FolderConfigManager $configManager;
	private IRootFolder $rootFolder;

	public function __construct(FolderConfigManager $configManager, IRootFolder $rootFolder) {
		parent::__construct();

		$this->configManager = $configManager;
		$this->rootFolder = $rootFolder;
	}

	protected function configure() {
		$this
			->setName('virtualfolder:create')
			->setDescription('Create a new virtual folder')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User id of the user to create the folder for'
			)
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name for the virtual folder'
			)
			->addArgument(
				'file_ids',
				InputArgument::IS_ARRAY,
				'File ids to add to the folder'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user');
		$mountPoint = $input->getArgument('mount_point');
		$fileIds = $input->getArgument('file_ids');

		$userFolder = $this->rootFolder->getUserFolder($userId);

		$fileIds = array_map(function ($id) use ($userFolder, $userId, $output) {
			$id = (int)$id;
			$nodes = $userFolder->getById($id);
			if (!$nodes) {
				$output->writeln("<error>No file with id $id found for $userId, skipping</error>");
				return null;
			}
			return $id;
		}, $fileIds);
		$fileIds = array_filter($fileIds);

		$hiddenFolder = $this->rootFolder->getHiddenUserFolder($userId);
		try {
			$virtualRootFolder = $hiddenFolder->get("virtualfolder");
		} catch (NotFoundException $e) {
			$virtualRootFolder = $hiddenFolder->newFolder("virtualfolder");
		}

		$this->configManager->newFolder($userId, $virtualRootFolder->getPath() . "/" . $mountPoint, $fileIds);

		return 0;
	}
}
