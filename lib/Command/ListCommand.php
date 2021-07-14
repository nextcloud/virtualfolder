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

use OC\Core\Command\Base;
use OCA\VirtualFolder\Folder\FolderConfig;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	/** @var FolderConfigManager */
	protected $configManager;

	public function __construct(FolderConfigManager $configManager) {
		parent::__construct();

		$this->configManager = $configManager;
	}

	protected function configure() {
		parent::configure();
		$this
			->setName('virtualfolder:list')
			->setDescription('List configured virtual folders');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$folders = $this->configManager->getAllFolders();

		$table = array_map(function(FolderConfig $folder) {
			return [
				"id" => $folder->getId(),
				"source_user" => $folder->getSourceUserId(),
				"target_user" => $folder->getTargetUserId(),
				"mount_point" => $folder->getMountPoint(),
				"files" => implode(", ", $folder->getSourceFileIds())
			];
		}, $folders);

		$this->writeArrayInOutputFormat($input, $output, $table);

		return 0;
	}
}
