<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

use \GdImage;
use \Exception;
use \Imagick;
use \ImagickException;
use Osumi\OsumiFramework\Tools\OTools;

/**
 * Utility class with tools to manipulate images (create new, resize, get information...)
 */
class OImage {
	private ?string  $filename   = null;
	private ?GdImage $image      = null;
	private ?int     $image_type = null;
	private array    $error_messages = [];

	function __construct() {
		$this->error_messages = [
			'FILE_NOT_FOUND'        => OTools::getMessage('PLUGIN_IMAGE_FILE_NOT_FOUND'),
			'LOAD_ERROR'            => OTools::getMessage('PLUGIN_IMAGE_LOAD_ERROR'),
			'FILE_NOT_LOADED'       => OTools::getMessage('PLUGIN_IMAGE_FILE_NOT_LOADED'),
			'INVALID_RATIO_FORMAT'  => OTools::getMessage('PLUGIN_IMAGE_INVALID_RATIO_FORMAT'),
			'INVALID_CROP_SIZE'     => OTools::getMessage('PLUGIN_IMAGE_INVALID_CROP_SIZE'),
			'CROP_NOT_POSSIBLE'     => OTools::getMessage('PLUGIN_IMAGE_CROP_NOT_POSSIBLE')
		];
	}

	/**
	 * Get Base64 encoded image file's extension
	 *
	 * @param string $data Base64 encoded image file
	 *
	 * @return string Image extension
	 */
	public static function getImageExtension(string $data): string {
		$arr_data = explode(';', $data);
		$arr_data = explode(':', $arr_data[0]);
		$arr_data = explode('/', $arr_data[1]);

		return $arr_data[1];
	}

	/**
	 * Save a Base64 encoded image file on given location
	 *
	 * @param string $path Path where the file should be saved
	 *
	 * @param string $base64_string Base64 encoded image file
	 *
	 * @param string $name Name of the resulting image file
	 *
	 * @param string $ext Extension of the resulting image file
	 *
	 * @param bool $overwrite Overwrite if image found on given location (default true)
	 *
	 * @return string Full path of the resulting image file
	 */
	public static function saveImage(string $path, string $base64_string, string $name, string $ext, bool $overwrite=true): string {
		$full_path = $path.$name.'.'.$ext;

		if (file_exists($full_path)) {
			unlink($full_path);
		}

		$ifp = fopen($full_path, "wb");
		$data = explode(',', $base64_string);
		fwrite($ifp, base64_decode($data[1]));
		fclose($ifp);

		return $full_path;
	}

	/**
	 * Get loaded files image type
	 *
	 * @return int Image type constant of the loaded file (or null if file hasn't been loaded yet)
	 */
	public function getImageType(): ?int {
		return $this->image_type;
	}

	/**
	 * Load into memory the specified file
	 *
	 * @param string $filename Path of the file to be loaded
	 *
	 * @return void
	 */
	public function load(string $filename): void {
		try {
			if (!file_exists($filename)) {
				throw new Exception(sprintf($this->error_messages['FILE_NOT_FOUND'], $filename), 100);
			}
			$this->filename   = $filename;
			$image_info       = getimagesize($filename);
			$this->image_type = $image_info[2];

			switch ($this->image_type) {
				case IMAGETYPE_JPEG: { $this->image = imagecreatefromjpeg($filename); }
				break;
				case IMAGETYPE_GIF: { $this->image = imagecreatefromgif($filename);  }
				break;
				case IMAGETYPE_PNG: { $this->image = imagecreatefrompng($filename);  }
				break;
				case IMAGETYPE_WEBP: { $this->image = imagecreatefromwebp($filename); }
				break;
				case IMAGETYPE_AVIF: { $this->image = imagecreatefromavif($filename); }
				break;
			}

			if (in_array($this->image_type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF])) {
				imagepalettetotruecolor($this->image);
				imagealphablending($this->image, true);
				imagesavealpha($this->image, true);
			}
		}
		catch(Exception $e) {
			$this->filename   = null;
			$this->image      = null;
			$this->image_type = null;
			if ($e->getCode() == 100) {
				throw new Exception($e->getMessage());
			}
			else {
				throw new Exception($this->error_messages['LOAD_ERROR']);
			}
		}
	}

	/**
	 * Save previously loaded file into the specified format, with a given compression rato and new file permissions
	 *
	 * @param string $filename Path of the new file to be created
	 *
	 * @param int $image_type New images file format
	 *
	 * @param int $compression Compression rate of the new file
	 *
	 * @param int | null $permissions Permissions of the new file
	 *
	 * @return void
	 */
	public function save(string $filename, int $image_type=IMAGETYPE_JPEG, int $compression=75, int | null $permissions = null): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		switch ($image_type) {
			case IMAGETYPE_JPEG: { imagejpeg($this->image, $filename, $compression); }
			break;
			case IMAGETYPE_GIF: { imagegif($this->image,  $filename); }
			break;
			case IMAGETYPE_PNG: { imagepng($this->image,  $filename); }
			break;
			case IMAGETYPE_WEBP: { imagewebp($this->image, $filename); }
			break;
			case IMAGETYPE_AVIF: { imageavif($this->image, $filename); }
			break;
		}
		if (!is_null($permissions)) {
			chmod($filename, $permissions);
		}
	}

	/**
	 * Change format of the loaded file
	 *
	 * @param int $image_type Format to be converted to
	 *
	 * @return void
	 */
	public function output(int $image_type=IMAGETYPE_JPEG): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		switch ($image_type) {
			case IMAGETYPE_JPEG: { imagejpeg($this->image); }
			break;
			case IMAGETYPE_GIF: {  imagegif($this->image);  }
			break;
			case IMAGETYPE_PNG: {  imagepng($this->image);  }
			break;
			case IMAGETYPE_WEBP: { imagewebp($this->image); }
			break;
			case IMAGETYPE_AVIF: { imageavif($this->image); }
			break;
		}
	}

	/**
	 * Get width of the loaded file
	 *
	 * @return int Width of the loaded file
	 */
	public function getWidth(): int {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		return imagesx($this->image);
	}

	/**
	 * Get height of the loaded file
	 *
	 * @return int Height of the loaded file
	 */
	public function getHeight(): int {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		return imagesy($this->image);
	}

	/**
	 * Resize loaded file to a fixed height mantaining the ratio
	 *
	 * @param int $height Height of the new file
	 *
	 * @return void
	 */
	public function resizeToHeight(int $height): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width, $height);
	}

	/**
	 * Resize loaded file to a fixed width mantaining the ratio
	 *
	 * @param int $width Width of the new file
	 *
	 * @return void
	 */
	public function resizeToWidth(int $width): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		$ratio  = $width / $this->getWidth();
		$height = intval($this->getHeight() * $ratio);
		$this->resize($width, $height);
	}

	/**
	 * Scale loaded file to a given percentage ratio
	 *
	 * @param int $scale Scale ratio to be resized to
	 *
	 * @return void
	 */
	public function scale(int $scale): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		$width  = $this->getWidth() * $scale/100;
		$height = $this->getHeight() * $scale/100;
		$this->resize($width, $height);
	}

	/**
	 * Resize image to a fixed width/height
	 *
	 * @param int $width New width of the loaded file
	 *
	 * @param int $height New height of the loaded file
	 *
	 * @return void
	 */
	public function resize(int $width, int $height): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		$new_image = imagecreatetruecolor($width, $height);
		if (in_array($this->image_type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF])) {
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
			$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
			imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
		}
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}

	/**
	 * Crop loaded image to a given ratio, keeping the image centered.
	 *
	 * Examples:
	 * - 16:9 => keep original width and cut top/bottom
	 * - 9:16 => keep original height and cut left/right
	 * - 1:1 => cut the exceeding side to get a square image
	 *
	 * @param string $ratio Target ratio in N:N format
	 *
	 * @return void
	 */
	public function cropToRatio(string $ratio): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}

		if (!preg_match('/^(\d+):(\d+)$/', $ratio, $matches)) {
			throw new Exception($this->error_messages['INVALID_RATIO_FORMAT']);
		}

		$ratio_width  = intval($matches[1]);
		$ratio_height = intval($matches[2]);

		if ($ratio_width <= 0 || $ratio_height <= 0) {
			throw new Exception($this->error_messages['INVALID_RATIO_FORMAT']);
		}

		$current_width  = $this->getWidth();
		$current_height = $this->getHeight();

		$target_ratio  = $ratio_width / $ratio_height;
		$current_ratio = $current_width / $current_height;

		if (abs($current_ratio - $target_ratio) < 0.00001) {
			return;
		}

		if ($target_ratio > $current_ratio) {
			$target_height = intval(round($current_width / $target_ratio));

			if ($target_height <= 0 || $target_height > $current_height) {
				throw new Exception($this->error_messages['CROP_NOT_POSSIBLE']);
			}

			$this->cropImage(
				0,
				intval(floor(($current_height - $target_height) / 2)),
				$current_width,
				$target_height
			);
			return;
		}

		$target_width = intval(round($current_height * $target_ratio));

		if ($target_width <= 0 || $target_width > $current_width) {
			throw new Exception($this->error_messages['CROP_NOT_POSSIBLE']);
		}

		$this->cropImage(
			intval(floor(($current_width - $target_width) / 2)),
			0,
			$target_width,
			$current_height
		);
	}

	/**
	 * Crop loaded image to an exact size, keeping the image centered.
	 *
	 * This method only crops, it never resizes.
	 *
	 * @param int $width Target width
	 *
	 * @param int $height Target height
	 *
	 * @return void
	 */
	public function cropToSize(int $width, int $height): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}

		if ($width <= 0 || $height <= 0) {
			throw new Exception($this->error_messages['INVALID_CROP_SIZE']);
		}

		$current_width  = $this->getWidth();
		$current_height = $this->getHeight();

		if ($width > $current_width || $height > $current_height) {
			throw new Exception($this->error_messages['CROP_NOT_POSSIBLE']);
		}

		if ($width === $current_width && $height === $current_height) {
			return;
		}

		$this->cropImage(
			intval(floor(($current_width - $width) / 2)),
			intval(floor(($current_height - $height) / 2)),
			$width,
			$height
		);
	}

	/**
	 * Crop loaded image to the specified area.
	 *
	 * @param int $src_x Source X coordinate
	 *
	 * @param int $src_y Source Y coordinate
	 *
	 * @param int $width Target width
	 *
	 * @param int $height Target height
	 *
	 * @return void
	 */
	private function cropImage(int $src_x, int $src_y, int $width, int $height): void {
		$new_image = imagecreatetruecolor($width, $height);

		if (in_array($this->image_type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF])) {
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
			$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
			imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
		}

		imagecopy(
			$new_image,
			$this->image,
			0,
			0,
			$src_x,
			$src_y,
			$width,
			$height
		);

		$this->image = $new_image;
	}

	/**
	 * Rotate image with given degrees. Doesn't work on GIF files
	 *
	 * @param int $degrees Number of degrees of rotation to be applied to the loaded image
	 *
	 * @return void
	 */
	public function rotate(int $degrees): void {
		if (is_null($this->image)) {
			throw new Exception($this->error_messages['FILE_NOT_LOADED']);
		}
		if ($this->image_type === IMAGETYPE_AVIF) {
			$source = imagecreatefromavif($this->filename);
			imagealphablending($source, false);
			imagesavealpha($source, true);

			$rotation = imagerotate($source, $degrees, imageColorAllocateAlpha($source, 0, 0, 0, 127));
			imagealphablending($rotation, false);
			imagesavealpha($rotation, true);

			$this->image = $rotation;
		}
		if ($this->image_type === IMAGETYPE_WEBP) {
			$source = imagecreatefromwebp($this->filename);
			imagealphablending($source, false);
			imagesavealpha($source, true);

			$rotation = imagerotate($source, $degrees, imageColorAllocateAlpha($source, 0, 0, 0, 127));
			imagealphablending($rotation, false);
			imagesavealpha($rotation, true);

			$this->image = $rotation;
		}
		if ($this->image_type === IMAGETYPE_PNG) {
			$source = imagecreatefrompng($this->filename);
			imagealphablending($source, false);
			imagesavealpha($source, true);

			$rotation = imagerotate($source, $degrees, imageColorAllocateAlpha($source, 0, 0, 0, 127));
			imagealphablending($rotation, false);
			imagesavealpha($rotation, true);

			$this->image = $rotation;
		}
		if ($this->image_type === IMAGETYPE_JPEG) {
			$source = imagecreatefromjpeg($this->filename);
			$rotation = imagerotate($source, $degrees, 0);
			$this->image = $rotation;
		}
	}

	/**
	 * Function to convert a PDF file into image files (one per page)
	 *
	 * @param string $pdf_path Path of the PDF file
	 *
	 * @param string $output_directory Path where generated images should be stored
	 *
	 * @param int $resolution Resolution of the generated images, defaults to 300
	 *
	 * @param int $jpg_quality Quality of the generated images (0-100), defaults to 90
	 *
	 * @return array List of generated images
	 */
	 public static function convertPdfToJpg(string $pdf_path, string $output_directory, int $resolution = 300, int $jpg_quality = 90): array {
 		// Check if output directory exists
 		if (!file_exists($output_directory)) {
 			mkdir($output_directory, 0777, true);
 		}

 		// Detect the operating system and normalize paths
 		if (PHP_OS_FAMILY === 'Windows') {
 			// Convert to Windows-style paths (backslashes)
 			$pdf_path = str_replace('/', '\\', $pdf_path);
 			$output_directory = str_replace('/', '\\', $output_directory);
 			$output_path = $output_directory . '\\' . pathinfo($pdf_path, PATHINFO_FILENAME) . '_%d.jpg';
 		}
 		else {
 			// Convert to UNIX-style paths (slashes)
 			$pdf_path = str_replace('\\', '/', $pdf_path);
 			$output_directory = str_replace('\\', '/', $output_directory);
 			$output_path = $output_directory . '/' . pathinfo($pdf_path, PATHINFO_FILENAME) . '_%d.jpg';
 		}

 		// Construct the Ghostscript command
 		$command = sprintf(
 			'gs -dNOPAUSE -sDEVICE=jpeg -r%d -dJPEGQ=%d -dBATCH -sOutputFile=%s %s',
 			$resolution,
 			$jpg_quality,
 			$output_path,
 			$pdf_path
 		);

 		// Execute the command
 		exec($command . ' 2>&1', $output, $return_code);

 		if ($return_code !== 0) {
 			throw new Exception("Error converting PDF using Ghostscript: {$command}\n" . var_export($output, true));
 		}

 		return glob($output_directory . '/' . pathinfo($pdf_path, PATHINFO_FILENAME) . '_*.jpg');
 	}
}
