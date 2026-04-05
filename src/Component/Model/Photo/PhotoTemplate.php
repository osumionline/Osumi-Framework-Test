<?php if (is_null($photo)): ?>
null
<?php else: ?>
{
	"id": {{ photo.id }},
	"idUser": {{ photo.id_user | number }},
	"ext": {{ photo.ext | string }},
	"alt": {{ photo.alt | string }},
	"url": {{ photo.url | string }},
	"createdAt": {{ photo.created_at | date }}
	"updatedAt": {{ photo.updated_at | date }}
}
<?php endif ?>
