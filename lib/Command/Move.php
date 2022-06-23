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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Move extends Command {
	protected FolderConfigManager $configManager;

	public function __construct(FolderConfigManager $configManager) {
		parent::__construct();

		$this->configManager = $configManager;
	}

	protected function configure() {
		$this
			->setName('virtualfolder:move')
			->setDescription('Change the mount point of a virtual folder')
			->addArgument(
				'folder_id',
				InputArgument::REQUIRED,
				'Id of the virtual folder'
			)
			->addArgument(
				'mount_point',
				InputArgument::REQUIRED,
				'Mount point for the virtual folder'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$folderId = (int)$input->getArgument('folder_id');
		$folder = $this->configManager->getById($folderId);

		if ($folder) {
			$this->configManager->setMountPoint($folderId, $input->getArgument('mount_point'));

			return 0;
		} else {
			$output->writeln("<error>folder with id $folderId not found</error>");
			return 1;
		}
	}
}
