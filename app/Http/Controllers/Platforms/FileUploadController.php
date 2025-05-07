<?php

namespace App\Http\Controllers\Platforms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
	public function upload(Request $request)
	{
		$request->validate([
			'file' => 'required|file|max:20480', // 20MB max
		]);

		$file = $request->file('file');
		$filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
		$mimeType = $file->getMimeType();

		// Check if the file is an image
		if (strpos($mimeType, 'image/') === 0 && $mimeType != 'image/gif') {
			// Image optimization process
			$manager = new ImageManager(new Driver());
			$image = $manager->read($file);

			// Resize if width is greater than 2000px
			$image->scale(width: 2000, height: 2000);

			// Quality reduction (80% quality usually maintains good visuals)
			$tempPath = storage_path('app/temp/' . $filename);

			// Convert to WebP if browser supports it
			// You could detect this from request headers or let the client decide
			if ($request->input('convert_to_webp', false)) {
				$filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
				$image->toWebp(80)->save($tempPath);
			} else {
				$image->save($tempPath, 80);
			}

			// Upload the optimized image
			$path = Storage::disk('backblaze')->putFileAs('uploads/images', $tempPath, $filename);

			// Remove temp file
			unlink($tempPath);
		} else {
			// For non-image files, upload directly without optimization
			$path = Storage::disk('backblaze')->putFileAs(
				'uploads/files',
				$file,
				$filename
			);
		}

		// Generate the URL
		$url = Storage::disk('backblaze')->url($path);

		return response()->json([
			'success' => true,
			'file_path' => $path,
			'url' => $url,
			'file_type' => $mimeType
		]);
	}
}
