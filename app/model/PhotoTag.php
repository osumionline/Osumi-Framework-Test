<?php declare(strict_types=1);
class PhotoTag extends OModel {
	function __construct() {
		$table_name  = 'photo_tag';
		$model = [
			'id_photo' => [
				'type'    => OCore::PK,
				'comment' => 'Photo id',
				'ref'     => 'photo.id'
			],
			'id_tag' => [
				'type'    => OCore::PK,
				'comment' => 'Tag id',
				'ref'     => 'tag.id'
			],
			'created_at' => [
				'type'    => OCore::CREATED,
				'comment' => 'Register creation date'
			]
		];

		parent::load($table_name, $model);
	}
}