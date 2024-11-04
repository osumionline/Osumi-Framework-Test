<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\App\Module\Home\Test;

use Osumi\OsumiFramework\Core\OComponent;
use Osumi\OsumiFramework\Web\ORequest;
use Osumi\OsumiFramework\ORM\ODB;
use Osumi\OsumiFramework\App\Model\Photo;
use Osumi\OsumiFramework\App\Model\User;

class TestComponent extends OComponent {
	/**
   * ¡La nueva acción <strong>Test</strong> funciona!
	 *
	 * @param ORequest $req Request object with method, headers, parameters and filters used
	 * @return void
	 */
	public function run(ORequest $req): void {
		$db = new ODB();

		echo "<strong>1.- ODB: SELECT sin parámetros.</strong>\n";
		echo "<br>\n";
		echo "SQL: SELECT * FROM `photo`\n";
		echo "<br>\n";
		$db->query("SELECT * FROM `photo`");
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($db->all());
		echo "</pre>\n";
		echo "NUM RESULTADOS: ".$db->count();
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>2.- ODB: SELECT con parámetros.</strong>\n";
		echo "<br>\n";
		echo "SQL: SELECT * FROM `photo` WHERE `id` = 1\n";
		echo "<br>\n";
		$db->query("SELECT * FROM `photo` WHERE `id` = ?", [1]);
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($db->all());
		echo "</pre>\n";
		echo "NUM RESULTADOS: ".$db->count();
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>3.- ODB: SELECT sin parámetros, recorriendo resultados.</strong>\n";
		echo "<br>\n";
		echo "SQL: SELECT * FROM `photo`\n";
		echo "<br>\n";
		$db->query("SELECT * FROM `photo`");
		echo "<fieldset>\n";
		echo "<pre>\n";
		while ($res = $db->next()) {
			var_dump($res);
			echo "\n";
		}
		echo "</pre>\n";
		echo "NUM RESULTADOS: ".$db->count();
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>4.- Debugeando ODB.</strong>\n";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($db);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>5.- Crear una instancia con create.</strong>\n";
		echo "<br>\n";
		echo "$"."photo = Photo::create();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$photo = Photo::create();
		var_dump($photo);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>6.- Crear una instancia con new.</strong>\n";
		echo "<br>\n";
		echo "$"."photo = new Photo();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$photo = new Photo();
		var_dump($photo);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>6.- Buscar un registro con findOne.</strong>\n";
		echo "<br>\n";
		echo "$"."user = User::findOne(['id' => 1]);";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$user = User::findOne(['id' => 1]);
		var_dump($user);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>7.- Manipulo ese registro obtenido.</strong>\n";
		echo "<br>\n";
		echo "$"."user->score++;";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$user->score++;
		var_dump($user);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>8.- Guardo el registro.</strong>\n";
		echo "<br>\n";
		echo "$"."user->save();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		if ($user->save()) {
			echo "REGISTRO GUARDADO.\n";
		}
		else {
			echo "ERROR AL GUARDAR";
		}
		echo "\n";
		var_dump($user);
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>9.- Buscar varios registros con where.</strong>\n";
		echo "<br>\n";
		echo "$"."photos = Photo::where(['id_user' => 1]);";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$photos = Photo::where(['id_user' => 1]);
		var_dump($photos);
		echo "\n";
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		$photo = Photo::findOne(['id' => 1]);

		echo "<strong>10.- Exportar a array.</strong>\n";
		echo "<br>\n";
		echo "$"."photo->toArray();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo->toArray());
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>11.- Exportar a JSON.</strong>\n";
		echo "<br>\n";
		echo "$"."photo->toJSON();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo->toJSON());
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>12.- Exportar a SQL.</strong>\n";
		echo "<br>\n";
		echo "$"."photo->toSQL();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo->toSQL());
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>13.- Transformar fecha con get.</strong>\n";
		echo "<br>\n";
		echo "$"."photo->get('created_at', 'd/m/Y');";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo->get('created_at', 'd/m/Y'));
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>14.- Transformar float con get.</strong>\n";
		echo "<br>\n";
		echo "$"."user->get('score', 3, ',', '');";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($user->get('score', 3, ',', ''));
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>15.- Obtener schema.</strong>\n";
		echo "<br>\n";
		echo "$"."user->getModel();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($user->getModel());
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		$db->query("SELECT * FROM `photo` WHERE `id` = ?", [1]);
		$res = $db->next();
		$photo = new Photo($res);
		echo "<strong>16.- Crear objeto usando el constructor.</strong>\n";
		echo "<br>\n";
		echo "$"."db->query(\"SELECT * FROM `photo` WHERE `id` = ?\", [1]);";
		echo "<br>\n";
		echo "$"."res = $"."db->next();";
		echo "<br>\n";
		echo "$"."photo = new Photo($"."res);";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo);
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		$photo = Photo::from($res);
		echo "<strong>17.- Crear objeto usando from.</strong>\n";
		echo "<br>\n";
		echo "$"."db->query(\"SELECT * FROM `photo` WHERE `id` = ?\", [1]);";
		echo "<br>\n";
		echo "$"."res = $"."db->next();";
		echo "<br>\n";
		echo "$"."photo = Photo::from($"."res);";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		var_dump($photo);
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>17.- Listar todos los registros.</strong>\n";
		echo "<br>\n";
		echo "$"."photos = Photo::all();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$photos = Photo::all();
		var_dump($photos);
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>18.- Crear un nuevo objeto.</strong>\n";
		echo "<br>\n";
		echo "$"."photo = Photo::create();\n";
		echo "<br>\n";
		echo "$"."photo->id_user = 2;\n";
		echo "<br>\n";
		echo "$"."photo->ext = 'webp';\n";
		echo "<br>\n";
		echo "$"."photo->alt = 'Alt text';\n";
		echo "<br>\n";
		echo "$"."photo->url = 'https://url...';\n";
		echo "<br>\n";
		echo "$"."photo->save();\n";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		$photo = Photo::create();
		$photo->id_user = 1;
		$photo->ext = 'webp';
		$photo->alt = 'Alt text';
		$photo->url = 'https://url...';
		if ($photo->save()) {
			echo "REGISTRO GUARDADO:\n";
			var_dump($photo);
		}
		else {
			echo "ERROR AL GUARDAR\n";
		}
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";

		echo "<strong>19.- Borrar el nuevo objeto.</strong>\n";
		echo "<br>\n";
		echo "$"."photo->delete();";
		echo "<br>\n";
		echo "<fieldset>\n";
		echo "<pre>\n";
		if ($photo->delete()) {
			echo "REGISTRO BORRADO:\n";
			var_dump($photo);
		}
		else {
			echo "ERROR AL BORRAR\n";
		}
		echo "</pre>\n";
		echo "<br>\n";
		echo "</fieldset>\n";
		echo "<br>\n";
	}
}
