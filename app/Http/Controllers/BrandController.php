<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     */
    public function index(Request $request)
    {
        try {
            // Smooth backward fallback to makers if table is empty
            if (Brand::count() === 0) {
                try {
                    return (new AttributeOptionController)->makers();
                } catch (\Exception $e) {
                    // continue
                }
            }

            $query = Brand::query();

            // Status filter
            if ($request->filled('status') && $request->status !== 'All') {
                $query->where('status', $request->status);
            }

            // Featured filter
            if ($request->has('featured')) {
                $query->where('feature_homepage', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
            }

            // Search query
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('country', 'like', "%{$search}%")
                      ->orWhere('tagline', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            $brands = $query->orderBy('sort_order', 'asc')
                            ->orderBy('title', 'asc')
                            ->get();

            return response()->json([
                'success' => true,
                'data'    => $brands,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load brands: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created brand with optional media upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        try {
            $data = $this->prepareData($request);

            // Handle Logo Upload
            if ($request->hasFile('logo')) {
                $data['logo'] = $this->uploadFile($request->file('logo'), 'logos');
            }

            // Handle Banner Upload
            if ($request->hasFile('banner')) {
                $data['banner'] = $this->uploadFile($request->file('banner'), 'banners');
            }

            $brand = Brand::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully.',
                'data'    => $brand,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create brand: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified brand.
     */
    public function show($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json(['success' => true, 'data' => $brand]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Brand not found.'], 404);
        }
    }

    /**
     * Update the specified brand with optional media replacement.
     */
    public function update(Request $request, $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $request->validate([
                'title' => 'required|string|max:255',
            ]);

            $data = $this->prepareData($request, $brand->id);

            // Replace Logo if new file uploaded
            if ($request->hasFile('logo')) {
                $this->deleteOldFile($brand->logo);
                $data['logo'] = $this->uploadFile($request->file('logo'), 'logos');
            }

            // Replace Banner if new file uploaded
            if ($request->hasFile('banner')) {
                $this->deleteOldFile($brand->banner);
                $data['banner'] = $this->uploadFile($request->file('banner'), 'banners');
            }

            $brand->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully.',
                'data'    => $brand,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update brand: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified brand and its associated media.
     */
    public function destroy($id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $this->deleteOldFile($brand->logo);
            $this->deleteOldFile($brand->banner);

            $brand->delete();

            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete brand: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to parse FormData scalars, booleans, and JSON arrays.
     */
    protected function prepareData(Request $request, ?int $ignoreId = null): array
    {
        $title = trim($request->input('title'));
        $slug = $request->filled('urlHandle')
            ? Str::slug($request->input('urlHandle'))
            : Brand::generateUniqueSlug($title, $ignoreId);

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'tagline'          => $request->input('tagline'),
            'description'      => $request->input('description'),
            'tier'             => $request->input('tier', 'Premium'),
            'sort_order'       => (int) $request->input('sortOrder', 0),
            'country'          => $request->input('country'),
            'headquarters'     => $request->input('headquarters'),
            'founded_year'     => $request->filled('foundedYear') ? (int) $request->input('foundedYear') : null,
            'website'          => $request->input('website'),
            'warranty_months'  => (int) $request->input('warrantyMonths', 12),
            'warranty_type'    => $request->input('warrantyType', 'Official brand warranty'),
            'supply_type'      => $request->input('supplyType', 'Official distributor'),
            'return_days'      => (int) $request->input('returnDays', 7),
            'warranty_terms'   => $request->input('warrantyTerms'),
            'service_centers'  => $request->input('serviceCenters'),
            'support_email'    => $request->input('supportEmail'),
            'support_phone'    => $request->input('supportPhone'),
            'support_url'      => $request->input('supportUrl'),
            'meta_title'       => $request->input('metaTitle'),
            'meta_description' => $request->input('metaDescription'),
            'status'           => $request->input('status', 'Active'),
            'is_official'      => filter_var($request->input('isOfficial'), FILTER_VALIDATE_BOOLEAN),
            'feature_homepage' => filter_var($request->input('featureHomepage'), FILTER_VALIDATE_BOOLEAN),
            'is_popular'       => filter_var($request->input('isPopular'), FILTER_VALIDATE_BOOLEAN),
            'show_in_nav'      => filter_var($request->input('showInNav', true), FILTER_VALIDATE_BOOLEAN),
            'show_in_filters'  => filter_var($request->input('showInFilters', true), FILTER_VALIDATE_BOOLEAN),
            'allow_reviews'    => filter_var($request->input('allowReviews', true), FILTER_VALIDATE_BOOLEAN),
        ];

        $jsonFields = ['categoryIds' => 'category_ids', 'certifications' => 'certifications', 'keywords' => 'keywords', 'social' => 'social'];
        foreach ($jsonFields as $reqKey => $dbKey) {
            if ($request->has($reqKey)) {
                $val = $request->input($reqKey);
                $data[$dbKey] = is_string($val) ? json_decode($val, true) : $val;
            }
        }

        return $data;
    }

    /**
     * Store uploaded file in public/uploads/brands/{folder}.
     */
    protected function uploadFile($file, string $folder): string
    {
        $destination = public_path("uploads/brands/{$folder}");
        if (!File::isDirectory($destination)) {
            File::makeDirectory($destination, 0755, true, true);
        }

        $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return "/uploads/brands/{$folder}/{$filename}";
    }

    /**
     * Delete existing media file if present on disk.
     */
    protected function deleteOldFile(?string $path): void
    {
        if ($path && Str::startsWith($path, '/uploads/brands/')) {
            $fullPath = public_path(ltrim($path, '/'));
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }
}