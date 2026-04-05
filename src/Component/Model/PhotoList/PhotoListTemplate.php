<?php
use Osumi\OsumiFramework\App\Component\Model\Photo\PhotoComponent;

foreach ($list as $i => $photo) {
  $component = new PhotoComponent([ 'photo' => $photo ]);
	echo strval($component);
	if ($i < count($list) - 1) {
		echo ",\n";
	}
}
