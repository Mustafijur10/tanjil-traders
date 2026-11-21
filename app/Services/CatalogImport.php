<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Excel import for the catalog. The browser reads the file and sends the rows as JSON;
 * every row is checked here first (dry run, shown as a preview), then imported one by one.
 * Rows are matched to existing records (by name, or SKU for products), so importing the same
 * file twice updates instead of duplicating. A bad row is skipped with its reason; the rest still import.
 *
 * The column lists below are the single source for templates, the preview and the import.
 */
class CatalogImport
{
    public const TYPES = ['categories', 'brands', 'attributes', 'products', 'stock'];

    public static function columns(): array
    {
        $yes = ['type' => 'yesno', 'help' => 'Yes or No'];

        return [
            'categories' => [
                ['key' => 'name', 'label' => 'Name', 'required' => true, 'example' => 'Android Phones'],
                ['key' => 'parent', 'label' => 'Parent category', 'example' => 'Smartphones', 'help' => 'Empty for a main category. Write the main category name to make this a subcategory.'],
                ['key' => 'description', 'label' => 'Description', 'example' => 'Phones running Android.'],
                ['key' => 'icon', 'label' => 'Icon', 'example' => 'mdi-cellphone', 'help' => 'Material Design icon name, optional.'],
                ['key' => 'show_in_nav', 'label' => 'Show in menu', 'example' => 'Yes'] + $yes,
                ['key' => 'is_featured', 'label' => 'Featured', 'example' => 'No'] + $yes,
                ['key' => 'is_active', 'label' => 'Active', 'example' => 'Yes'] + $yes,
                ['key' => 'meta_title', 'label' => 'SEO title', 'example' => ''],
                ['key' => 'meta_description', 'label' => 'SEO description', 'example' => ''],
            ],
            'brands' => [
                ['key' => 'name', 'label' => 'Name', 'required' => true, 'example' => 'Samsung'],
                ['key' => 'description', 'label' => 'Description', 'example' => 'Official Samsung products.'],
                ['key' => 'is_featured', 'label' => 'Featured', 'example' => 'Yes'] + $yes,
                ['key' => 'is_active', 'label' => 'Active', 'example' => 'Yes'] + $yes,
                ['key' => 'meta_title', 'label' => 'SEO title', 'example' => ''],
                ['key' => 'meta_description', 'label' => 'SEO description', 'example' => ''],
            ],
            'attributes' => [
                ['key' => 'name', 'label' => 'Spec name', 'required' => true, 'example' => 'RAM'],
                ['key' => 'group', 'label' => 'Group', 'example' => 'Memory', 'help' => 'Heading in the spec table, e.g. Memory, Display, Battery.'],
                ['key' => 'unit', 'label' => 'Unit', 'example' => 'GB'],
                ['key' => 'values', 'label' => 'Values', 'example' => '4GB, 6GB, 8GB, 12GB', 'help' => 'Separate with commas. Empty for a free-text spec.'],
                ['key' => 'is_filterable', 'label' => 'Shop filter', 'example' => 'Yes'] + $yes,
                ['key' => 'is_comparable', 'label' => 'In compare', 'example' => 'Yes'] + $yes,
                ['key' => 'is_variant', 'label' => 'Used for options', 'example' => 'Yes', 'help' => 'Yes for things customers choose, like Storage or Colour.'] + $yes,
            ],
            'products' => [
                ['key' => 'name', 'label' => 'Name', 'required' => true, 'example' => 'Samsung Galaxy A55 5G'],
                ['key' => 'sku', 'label' => 'SKU', 'example' => 'SAM-A55-8-128', 'help' => 'Your stock code. Products are matched by SKU, or by name when empty.'],
                ['key' => 'category', 'label' => 'Category', 'required' => true, 'example' => 'Smartphones > Android Phones', 'help' => 'Subcategory name, or "Main > Sub" when the name is used twice.'],
                ['key' => 'brand', 'label' => 'Brand', 'example' => 'Samsung', 'help' => 'Created if it does not exist.'],
                ['key' => 'price', 'label' => 'Price', 'required' => true, 'type' => 'number', 'example' => 52999],
                ['key' => 'sale_price', 'label' => 'Sale price', 'type' => 'number', 'example' => ''],
                ['key' => 'cost_price', 'label' => 'Cost price', 'type' => 'number', 'example' => 46500, 'help' => 'What you paid. Never shown to customers.'],
                ['key' => 'stock_qty', 'label' => 'Stock', 'type' => 'number', 'example' => 10],
                ['key' => 'warranty', 'label' => 'Warranty', 'example' => '1 year official warranty'],
                ['key' => 'short_description', 'label' => 'Key features', 'example' => "Super AMOLED 120Hz display\nIP67 water resistance", 'help' => 'One feature per line (Alt+Enter in Excel).'],
                ['key' => 'description', 'label' => 'Description', 'example' => 'The Galaxy A55 brings a premium metal frame…'],
                ['key' => 'specs', 'label' => 'Specs', 'example' => 'RAM: 8GB; Storage: 128GB; Network: 5G', 'help' => 'Spec: value; separated by semicolons. Missing specs and values are created.'],
                ['key' => 'images', 'label' => 'Image links', 'example' => 'https://example.com/a55-front.jpg, https://example.com/a55-back.jpg', 'help' => 'Full image links separated by commas. Empty keeps current photos.'],
                ['key' => 'status', 'label' => 'Status', 'example' => 'Published', 'help' => 'Published or Draft.'],
                ['key' => 'is_featured', 'label' => 'Featured', 'example' => 'No'] + $yes,
                ['key' => 'meta_title', 'label' => 'SEO title', 'example' => ''],
                ['key' => 'meta_description', 'label' => 'SEO description', 'example' => ''],
            ],
            'stock' => [
                ['key' => 'sku', 'label' => 'SKU', 'required' => true, 'example' => 'SAM-A55-8-128', 'help' => 'Product or option SKU.'],
                ['key' => 'stock_qty', 'label' => 'Counted stock', 'required' => true, 'type' => 'number', 'example' => 12, 'help' => 'The quantity you have now. The change is logged in Stock Movements.'],
                ['key' => 'cost_price', 'label' => 'Cost price', 'type' => 'number', 'example' => '', 'help' => 'Optional: update the cost too.'],
                ['key' => 'note', 'label' => 'Note', 'example' => 'Stock count 21 Nov'],
            ],
        ];
    }

    /**
     * @return array{results: array, summary: array}
     */
    public function run(string $type, array $rows, bool $dryRun, ?int $userId = null): array
    {
        $results = [];
        $this->cache = [];

        // Main categories first, so subcategories in the same file find their parent
        $order = array_keys($rows);
        if ($type === 'categories') {
            usort($order, fn ($a, $b) => (blank($rows[$a]['parent'] ?? null) ? 0 : 1) <=> (blank($rows[$b]['parent'] ?? null) ? 0 : 1) ?: $a <=> $b);
        }

        // A preview runs the real import inside one transaction and rolls everything back at the end,
        // so rows that depend on earlier rows (a subcategory of a new category) are checked correctly.
        if ($dryRun) {
            DB::beginTransaction();
        }
        try {
            foreach ($order as $i) {
                $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $rows[$i]);
                $label = $row['name'] ?? $row['sku'] ?? '';
                try {
                    // Each row in its own transaction (a savepoint during a preview): a bad row leaves nothing behind
                    $action = DB::transaction(fn () => $this->{'import' . Str::studly($type)}($row, $dryRun, $userId));
                    $results[$i] = ['row' => $i + 2, 'status' => $action[0], 'name' => $action[1], 'errors' => []];
                } catch (ValidationException $e) {
                    $results[$i] = ['row' => $i + 2, 'status' => 'error', 'name' => $label, 'errors' => collect($e->errors())->flatten()->values()->all()];
                } catch (Throwable $e) {
                    report($e);
                    $results[$i] = ['row' => $i + 2, 'status' => 'error', 'name' => $label, 'errors' => ['Could not save this row: ' . Str::limit($e->getMessage(), 160)]];
                }
            }
        } finally {
            if ($dryRun) {
                DB::rollBack();
            }
        }
        ksort($results);
        $results = array_values($results);

        return [
            'results' => $results,
            'summary' => [
                'create' => count(array_filter($results, fn ($r) => $r['status'] === 'create')),
                'update' => count(array_filter($results, fn ($r) => $r['status'] === 'update')),
                'error'  => count(array_filter($results, fn ($r) => $r['status'] === 'error')),
            ],
        ];
    }

    private array $cache = [];

    // ── Row importers: return [create|update, label] or throw ValidationException ──

    private function importCategories(array $r): array
    {
        $this->need($r, 'name');
        $parentId = null;
        if (filled($r['parent'] ?? null)) {
            $parent = Category::whereNull('parent_id')->whereRaw('lower(name) = ?', [mb_strtolower($r['parent'])])->first();
            $this->check($parent, "Parent category “{$r['parent']}” was not found. Add it as a main category first (it can be in this file).");
            $parentId = $parent->id;
        }
        $cat = Category::where('parent_id', $parentId)->whereRaw('lower(name) = ?', [mb_strtolower($r['name'])])->first();
        $fields = $this->only($r, ['description', 'icon', 'meta_title', 'meta_description']) + $this->flags($r, ['show_in_nav', 'is_featured', 'is_active']);

        if ($cat) {
            $cat->update($fields);

            return ['update', $r['name']];
        }
        Category::create($fields + ['name' => $r['name'], 'parent_id' => $parentId, 'slug' => Slug::unique(Category::class, null, $r['name']), 'sort_order' => (int) Category::where('parent_id', $parentId)->max('sort_order') + 1]);

        return ['create', $r['name']];
    }

    private function importBrands(array $r): array
    {
        $this->need($r, 'name');
        $brand = Brand::whereRaw('lower(name) = ?', [mb_strtolower($r['name'])])->first();
        $fields = $this->only($r, ['description', 'meta_title', 'meta_description']) + $this->flags($r, ['is_featured', 'is_active']);
        if ($brand) {
            $brand->update($fields);

            return ['update', $r['name']];
        }
        Brand::create($fields + ['name' => $r['name'], 'slug' => Slug::unique(Brand::class, null, $r['name']), 'sort_order' => (int) Brand::max('sort_order') + 1]);

        return ['create', $r['name']];
    }

    private function importAttributes(array $r): array
    {
        $this->need($r, 'name');
        $attr = Attribute::whereRaw('lower(name) = ?', [mb_strtolower($r['name'])])->first();
        $values = collect(explode(',', (string) ($r['values'] ?? '')))->map(fn ($v) => trim($v))->filter()->unique()->values();
        $fields = $this->only($r, ['group', 'unit']) + $this->flags($r, ['is_filterable', 'is_comparable', 'is_variant']);
        $action = $attr ? 'update' : 'create';

        if ($attr) {
            $attr->update($fields);
        } else {
            $attr = Attribute::create($fields + ['name' => $r['name'], 'slug' => Slug::unique(Attribute::class, null, $r['name']), 'type' => $values->isEmpty() ? 'text' : 'select']);
        }
        foreach ($values as $v) {
            $this->valueId($attr, $v);
        }

        return [$action, $r['name']];
    }

    private function importProducts(array $r, bool $dryRun): array
    {
        $this->need($r, 'name');
        $this->need($r, 'category');
        $this->need($r, 'price');
        foreach (['price', 'sale_price', 'cost_price', 'stock_qty'] as $n) {
            if (filled($r[$n] ?? null)) {
                $this->check(is_numeric($r[$n]) && $r[$n] >= 0, "“{$this->label('products', $n)}” must be a number.");
            }
        }
        if (filled($r['sale_price'] ?? null)) {
            $this->check($r['sale_price'] < $r['price'], 'Sale price must be lower than the price.');
        }

        $category = $this->category($r['category']);
        $brandId = filled($r['brand'] ?? null) ? $this->brandId($r['brand']) : null;

        // Match by SKU; a product with the same name and no SKU yet is the same product (it gets the SKU)
        $byName = fn () => Product::whereRaw('lower(name) = ?', [mb_strtolower($r['name'])]);
        $product = filled($r['sku'] ?? null)
            ? (Product::where('sku', $r['sku'])->first() ?? $byName()->whereNull('sku')->first())
            : $byName()->first();

        $data = [
            'name'              => $r['name'],
            'sku'               => $r['sku'] ?? null,
            'category_id'       => $category->id,
            'brand_id'          => $brandId,
            'price'             => (float) $r['price'],
            'sale_price'        => filled($r['sale_price'] ?? null) ? (float) $r['sale_price'] : null,
            'cost_price'        => filled($r['cost_price'] ?? null) ? (float) $r['cost_price'] : ($product?->cost_price),
            'warranty'          => $r['warranty'] ?? $product?->warranty,
            'short_description' => $r['short_description'] ?? $product?->short_description,
            'description'       => filled($r['description'] ?? null) ? nl2br(e($r['description'])) : $product?->description,
            'status'            => mb_strtolower((string) ($r['status'] ?? '')) === 'draft' ? 'draft' : (filled($r['status'] ?? null) || ! $product ? 'active' : $product->status),
            'is_featured'       => $this->yes($r['is_featured'] ?? null, $product?->is_featured ?? false),
            'meta_title'        => $r['meta_title'] ?? $product?->meta_title,
            'meta_description'  => $r['meta_description'] ?? $product?->meta_description,
        ];

        // Products with options keep their options; only the basic details change
        if ($product && $product->variants()->exists()) {
            $product->update(collect($data)->except(['price', 'sale_price'])->all());

            return ['update', $r['name'] . ' (options kept)'];
        }

        $data['stock_qty'] = filled($r['stock_qty'] ?? null) ? (int) $r['stock_qty'] : ($product?->stock_qty ?? 0);
        $data['images'] = filled($r['images'] ?? null)
            ? collect(preg_split('/[\s,]+/', $r['images']))->filter(fn ($u) => preg_match('#^https?://#i', $u))->map(fn ($u) => ['path' => $u, 'alt' => $r['name']])->values()->all()
            : ($product?->images()->get(['path', 'alt'])->toArray() ?? []);
        $data['specs'] = filled($r['specs'] ?? null)
            ? $this->specs($r['specs'])
            : ($product?->specs()->get()->groupBy('attribute_id')->map(fn ($g, $a) => ['attribute_id' => $a, 'value_ids' => $g->pluck('attribute_value_id')->filter()->values()->all(), 'value' => $g->pluck('value')->filter()->first()])->values()->all() ?? []);

        app(ProductSaver::class)->save($data, $product);

        return [$product ? 'update' : 'create', $r['name']];
    }

    private function importStock(array $r, bool $dryRun, ?int $userId): array
    {
        $this->need($r, 'sku');
        $this->check(isset($r['stock_qty']) && is_numeric($r['stock_qty']) && $r['stock_qty'] >= 0, '“Counted stock” must be 0 or more.');

        $variant = ProductVariant::where('sku', $r['sku'])->first();
        $product = $variant?->product ?? Product::where('sku', $r['sku'])->first();
        $this->check($product, "No product or option has the SKU “{$r['sku']}”.");
        $this->check($variant || ! $product->variants()->exists(), 'This product has options. Use each option’s SKU.');

        if (filled($r['cost_price'] ?? null)) {
            $this->check(is_numeric($r['cost_price']), '“Cost price” must be a number.');
            $product->update(['cost_price' => (float) $r['cost_price']]);
        }
        $change = (int) $r['stock_qty'] - ($variant ?? $product)->stock_qty;
        if ($change !== 0) {
            StockMovement::record($product, $variant, $change, 'adjustment', ['note' => $r['note'] ?? 'Excel stock import', 'user_id' => $userId]);
        }
        $label = $product->name . ($variant ? ' · ' . $variant->load('values')->label() : '');

        return ['update', $label . ($change ? sprintf(' (%+d)', $change) : ' (no change)')];
    }

    // ── helpers ──

    private function category(string $text): Category
    {
        [$main, $sub] = array_pad(array_map('trim', explode('>', $text, 2)), 2, null);
        $query = $sub
            ? Category::whereRaw('lower(name) = ?', [mb_strtolower($sub)])->whereHas('parent', fn ($p) => $p->whereRaw('lower(name) = ?', [mb_strtolower($main)]))
            : Category::whereRaw('lower(name) = ?', [mb_strtolower($main)]);
        $found = $query->get();
        $this->check($found->isNotEmpty(), "Category “{$text}” was not found. Import categories first.");
        $this->check($found->count() === 1, "There are several categories called “{$text}”. Write it as “Main > Sub”.");

        return $found->first();
    }

    private function brandId(string $name): int
    {
        return $this->cache['brand:' . mb_strtolower($name)] ??= (Brand::whereRaw('lower(name) = ?', [mb_strtolower($name)])->value('id')
            ?? Brand::create(['name' => $name, 'slug' => Slug::unique(Brand::class, null, $name), 'is_active' => true])->id);
    }

    /** "RAM: 8GB; Storage: 128GB" -> ProductSaver specs */
    private function specs(string $text): array
    {
        $specs = [];
        foreach (array_filter(array_map('trim', explode(';', $text))) as $pair) {
            [$name, $value] = array_pad(array_map('trim', explode(':', $pair, 2)), 2, '');
            $this->check($name !== '' && $value !== '', "Spec “{$pair}” must look like “Name: value”.");
            $attr = Attribute::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first()
                ?? Attribute::create(['name' => $name, 'slug' => Slug::unique(Attribute::class, null, $name), 'type' => 'select']);
            $specs[] = $attr->type === 'text'
                ? ['attribute_id' => $attr->id, 'value' => $value]
                : ['attribute_id' => $attr->id, 'value_ids' => [$this->valueId($attr, $value)]];
        }

        return $specs;
    }

    private function valueId(Attribute $attr, string $value): int
    {
        $existing = $attr->values()->whereRaw('lower(value) = ?', [mb_strtolower($value)])->value('id');

        return $existing ?? $attr->values()->create([
            'value'      => $value,
            'slug'       => Str::slug($value) ?: Str::random(6),
            'sort_order' => (int) $attr->values()->max('sort_order') + 1,
        ])->id;
    }

    private function only(array $r, array $keys): array
    {
        return collect($keys)->filter(fn ($k) => array_key_exists($k, $r) && $r[$k] !== null && $r[$k] !== '')->mapWithKeys(fn ($k) => [$k => $r[$k]])->all();
    }

    private function flags(array $r, array $keys): array
    {
        return collect($keys)->filter(fn ($k) => filled($r[$k] ?? null))->mapWithKeys(fn ($k) => [$k => $this->yes($r[$k])])->all();
    }

    private function yes($v, bool $default = true): bool
    {
        if ($v === null || $v === '') {
            return $default;
        }
        if (is_bool($v)) {
            return $v;
        }

        return in_array(mb_strtolower(trim((string) $v)), ['yes', 'y', 'true', '1', 'হ্যাঁ', 'on'], true);
    }

    private function need(array $r, string $key): void
    {
        $this->check(filled($r[$key] ?? null), "“{$this->label(null, $key)}” is required.");
    }

    private function check($ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['row' => $message]);
        }
    }

    private function label(?string $type, string $key): string
    {
        foreach (self::columns() as $t => $cols) {
            if ($type && $t !== $type) {
                continue;
            }
            foreach ($cols as $c) {
                if ($c['key'] === $key) {
                    return $c['label'];
                }
            }
        }

        return $key;
    }
}
