<?php

declare(strict_types=1);

namespace OCA\VirtualFolder\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version001000Date20210713140403 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('virtual_folders')) {
			$table = $schema->createTable('virtual_folders');
			$table->addColumn('folder_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('user', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('mount_point', 'string', [
				'notnull' => true,
				'length' => 512,
			]);
			$table->setPrimaryKey(['folder_id']);
			$table->addIndex(['user'], 'vf_user');
		}

		if (!$schema->hasTable('virtual_folder_files')) {
			$table = $schema->createTable('virtual_folder_files');
			$table->addColumn('folder_file_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('folder_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->setPrimaryKey(['folder_file_id']);
			$table->addIndex(['folder_id'], 'vff_folder');
		}

		return $schema;
	}
}
