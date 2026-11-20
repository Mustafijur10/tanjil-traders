<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * One upload endpoint for every admin image (logos, banners, products, categories ...).
 * Files go to storage/app/public/{folder}; the returned `path` is what gets saved in the database.
 */
class UploadController extends Controller
{
    private const FOLDERS = ['brands', 'categories', 'products', 'banners', 'pages', 'store'];

    public function store(Request $request)
    {
        $data = $request->validate([
            'file'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
            'folder' => ['required', 'in:' . implode(',', self::FOLDERS)],
        ]);

        $path = $request->file('file')->store($data['folder'], 'public');

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ], 201);
    }
}
