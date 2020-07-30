<?php declare(strict_types=1);
class Tag extends OModel {
	function __construct() {
		$table_name  = 'tag';
		$model = [
			'id' => [
				'type'    => OCore::PK,
				'comment' => 'Unique id for each tag'
			],
			'name' => [
				'type'     => OCore::TEXT,
				'size'     => 20,
				'nullable' => false,
				'comment'  => 'Tags name'
			],
			'id_user' => [
				'type'     => OCore::NUM,
				'nullable' => true,
				'default'  => null,
				'comment'  => 'User id',
				'ref'      => 'user.id'
			],
			'created_at' => [
				'type'    => OCore::CREATED,
				'comment' => 'Register creation date'
			],
			'updated_at' => [
				'type'    => OCore::UPDATED,
				'comment' => 'Registers last update date'
			]
		];

		parent::load($table_name, $model);
	}

	public function __toString() {
		return $this->get('name');
	}
}