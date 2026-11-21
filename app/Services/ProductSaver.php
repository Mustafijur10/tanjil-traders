<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\Html;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves a product with its images, specs and variants in one transaction.
 * Stock typed into the form is logged as a stock movement, so the history always adds up.
 */
class ProductSaver
{
    public function save(array $data, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($data, $product) {
            $isNew = ! $product;
            $product ??= new Product();
            $oldStock = $isNew ? 0 : $product->stock_qty;

            $fields = collect($data)->only([
                'category_id', 'brand_id', 'name', 'sku', 'short_description', 'description',
                'price', 'sale_price', 'sale_ends_at', 'cost_price', 'warranty',
                'is_featured', 'status', 'meta_title', 'meta_description',
            ])->all();
            $fields['slug'] = Slug::unique(Product::class, $data['slug'] ?? null, $data['name'], $product->id);
            $fields['description'] = Html::clean($fields['description'] ?? null);
            $fields['sku'] = $fields['sku'] ?? null ?: null;

            $variants = $data['variants'] ?? [];
            if (! $variants) {
                $fields['stock_qty'] = (int) ($data['stock_qty'] ?? 0);
            }

            $product->fill($fields)->save();

            $this->saveImages($product, $data['images'] ?? []);
            $this->saveSpecs($product, $data['specs'] ?? []);

            if ($variants) {
                $this->saveVariants($product, $variants);
                $this->addVariantSpecs($product);
                $product->syncFromVariants();
            } else {
                // Variants were removed: the product holds its own stock again.
                $product->variants()->delete();
                $this->logStock($product, null, $oldStock, $product->stock_qty, $isNew);
            }

            return $product->refresh();
        });
    }

    private function saveImages(Product $product, array $images): void
    {
        $product->images()->delete();
        foreach (array_values($images) as $i => $img) {
            $product->images()->create([
                'path'       => $img['path'],
                'alt'        => $img['alt'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    /** specs: [{ attribute_id, value_ids: [..] } | { attribute_id, value: "text" }] */
    private function saveSpecs(Product $product, array $specs): void
    {
        $product->specs()->delete();

        $valueIds = collect($specs)->pluck('value_ids')->flatten()->filter()->unique();
        $owners = AttributeValue::whereIn('id', $valueIds)->pluck('attribute_id', 'id');

        $rows = [];
        foreach ($specs as $spec) {
            foreach (array_unique($spec['value_ids'] ?? []) as $valueId) {
                if (($owners[$valueId] ?? null) !== (int) $spec['attribute_id']) {
                    throw ValidationException::withMessages(['specs' => 'A spec value does not belong to its spec. Please reload the page.']);
                }
                $rows[] = ['product_id' => $product->id, 'attribute_id' => $spec['attribute_id'], 'attribute_value_id' => $valueId, 'value' => null];
            }
            $text = trim((string) ($spec['value'] ?? ''));
            if ($text !== '') {
                $rows[] = ['product_id' => $product->id, 'attribute_id' => $spec['attribute_id'], 'attribute_value_id' => null, 'value' => $text];
            }
        }

        if ($rows) {
            DB::table('product_specs')->insert($rows);
        }
    }

    /** variants: [{ id?, sku, price, sale_price, stock_qty, image, is_active, value_ids: [..] }] */
    private function saveVariants(Product $product, array $variants): void
    {
        $existing = $product->variants()->get()->keyBy('id');
        $keep = [];
        $seen = [];

        foreach ($variants as $row) {
            $valueIds = collect($row['value_ids'] ?? [])->map(fn ($v) => (int) $v)->sort()->values()->all();
            $key = implode('-', $valueIds);
            if (! $valueIds || isset($seen[$key])) {
                throw ValidationException::withMessages(['variants' => 'Each variant needs a different combination of options.']);
            }
            $seen[$key] = true;

            $variant = isset($row['id']) && $existing->has($row['id']) ? $existing[$row['id']] : new ProductVariant(['product_id' => $product->id]);
            $old = $variant->exists ? $variant->stock_qty : 0;

            $variant->fill([
                'sku'        => ($row['sku'] ?? null) ?: null,
                'price'      => $row['price'],
                'sale_price' => $row['sale_price'] ?? null,
                'stock_qty'  => (int) ($row['stock_qty'] ?? 0),
                'image'      => $row['image'] ?? null,
                'is_active'  => $row['is_active'] ?? true,
            ]);
            $variant->product_id = $product->id;
            $variant->save();
            $variant->values()->sync($valueIds);

            $this->logStock($product, $variant, $old, $variant->stock_qty, ! $existing->has($variant->id));
            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->delete();
    }

    /**
     * Options used by active variants also count as the product's specs,
     * so a shop filter for "256GB" finds a phone sold in 128GB and 256GB.
     */
    private function addVariantSpecs(Product $product): void
    {
        $existing = DB::table('product_specs')->where('product_id', $product->id)->whereNotNull('attribute_value_id')->pluck('attribute_value_id')->all();

        $rows = DB::table('product_variant_values')
            ->join('product_variants', 'product_variants.id', '=', 'product_variant_values.variant_id')
            ->join('attribute_values', 'attribute_values.id', '=', 'product_variant_values.attribute_value_id')
            ->where('product_variants.product_id', $product->id)
            ->where('product_variants.is_active', true)
            ->whereNotIn('attribute_values.id', $existing ?: [0])
            ->distinct()
            ->get(['attribute_values.id as value_id', 'attribute_values.attribute_id']);

        if ($rows->isNotEmpty()) {
            DB::table('product_specs')->insert($rows->map(fn ($r) => [
                'product_id' => $product->id, 'attribute_id' => $r->attribute_id, 'attribute_value_id' => $r->value_id, 'value' => null,
            ])->all());
        }
    }

    private function logStock(Product $product, ?ProductVariant $variant, int $old, int $new, bool $isNew): void
    {
        if ($old === $new) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'change'     => $new - $old,
            'balance'    => $new,
            'reason'     => $isNew ? 'restock' : 'adjustment',
            'note'       => $isNew ? 'Opening stock' : 'Changed in the product form',
            'user_id'    => auth()->id(),
        ]);
    }
}
