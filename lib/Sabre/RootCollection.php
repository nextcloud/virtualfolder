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
namespace OCA\VirtualFolder\Sabre;

use OCA\Files_Trashbin\Trash\ITrashManager;
use OCA\VirtualFolder\Folder\FolderConfigManager;
use OCA\VirtualFolder\Folder\VirtualFolderFactory;
use OCA\VirtualFolder\Mount\VirtualFolderMountProvider;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IUserSession;
use Sabre\DAV\INode;
use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\PrincipalBackend;

class RootCollection extends AbstractPrincipalCollection {
	/** @var FolderConfigManager */
	private $configManager;
	/** @var IUserSession */
	private $userSession;
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(
		FolderConfigManager $configManager,
		IUserSession $userSession,
		IRootFolder $rootFolder,
		PrincipalBackend\BackendInterface $principalBackend
	) {
		parent::__construct($principalBackend, 'principals/users');

		$this->configManager = $configManager;
		$this->userSession = $userSession;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principalInfo
	 * @return INode
	 */
	public function getChildForPrincipal(array $principalInfo): VirtualFolderHome {
		[, $name] = \Sabre\Uri\split($principalInfo['uri']);
		$user = $this->userSession->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}
		return new VirtualFolderHome($principalInfo, $this->configManager, $user, $this->rootFolder);
	}

	public function getName(): string {
		return 'virtualfolder';
	}
}
