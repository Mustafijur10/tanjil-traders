<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CatalogImport;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /** Column lists for templates and the preview. */
    public function types()
    {
        return response()->json(CatalogImport::columns());
    }

    /** Current records in the template's columns, to edit in Excel and import again. */
    public function export(Request $request, string $type)
    {
        abort_unless(in_array($type, CatalogImport::TYPES, true), 404);
        abort_unless($request->user()->canAccess($type === 'stock' ? 'inventory' : 'catalog'), 403);
        $yes = fn ($b) => $b ? 'Yes' : 'No';

        $rows = match ($type) {
            'categories' => \App\Models\Category::with('parent:id,name')->orderByRaw('parent_id is not null')->orderBy('sort_order')->get()->map(fn ($c) => [
                'name' => $c->name, 'parent' => $c->parent?->name, 'description' => $c->description, 'icon' => $c->icon,
                'show_in_nav' => $yes($c->show_in_nav), 'is_featured' => $yes($c->is_featured), 'is_active' => $yes($c->is_active),
                'meta_title' => $c->meta_title, 'meta_description' => $c->meta_description,
            ]),
            'brands' => \App\Models\Brand::orderBy('sort_order')->get()->map(fn ($b) => [
                'name' => $b->name, 'description' => $b->description, 'is_featured' => $yes($b->is_featured), 'is_active' => $yes($b->is_active),
                'meta_title' => $b->meta_title, 'meta_description' => $b->meta_description,
            ]),
            'attributes' => \App\Models\Attribute::with('values')->orderBy('sort_order')->get()->map(fn ($a) => [
                'name' => $a->name, 'group' => $a->group, 'unit' => $a->unit, 'values' => $a->values->pluck('value')->implode(', '),
                'is_filterable' => $yes($a->is_filterable), 'is_comparable' => $yes($a->is_comparable), 'is_variant' => $yes($a->is_variant),
            ]),
            'products' => \App\Models\Product::with(['category.parent', 'brand', 'images', 'specs.attribute', 'specs.attributeValue'])->orderBy('name')->get()->map(fn ($p) => [
                'name' => $p->name, 'sku' => $p->sku,
                'category' => $p->category ? ($p->category->parent ? $p->category->parent->name . ' > ' : '') . $p->category->name : null,
                'brand' => $p->brand?->name, 'price' => $p->price, 'sale_price' => $p->sale_price, 'cost_price' => $p->cost_price,
                'stock_qty' => $p->stock_qty, 'warranty' => $p->warranty, 'short_description' => $p->short_description,
                'description' => trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", (string) $p->description)))),
                'specs' => $p->specs->groupBy('attribute_id')->map(fn ($g) => $g->first()->attribute?->name . ': ' . $g->map(fn ($s) => $s->attributeValue->value ?? $s->value)->implode(', '))->implode('; '),
                'images' => $p->images->pluck('path')->filter(fn ($u) => str_starts_with($u, 'http'))->implode(', '),
                'status' => $p->status === 'active' ? 'Published' : 'Draft', 'is_featured' => $yes($p->is_featured),
                'meta_title' => $p->meta_title, 'meta_description' => $p->meta_description,
            ]),
            'stock' => \App\Models\Product::with('variants.values')->whereNotNull('sku')->orWhereHas('variants', fn ($v) => $v->whereNotNull('sku'))->orderBy('name')->get()
                ->flatMap(fn ($p) => $p->variants->isNotEmpty()
                    ? $p->variants->filter(fn ($v) => $v->sku)->map(fn ($v) => ['sku' => $v->sku, 'stock_qty' => $v->stock_qty, 'cost_price' => $p->cost_price, 'note' => $p->name . ' · ' . $v->label()])
                    : [['sku' => $p->sku, 'stock_qty' => $p->stock_qty, 'cost_price' => $p->cost_price, 'note' => $p->name]])->values(),
        };

        return response()->json($rows);
    }

    /** Body: { rows: [{ name: ..., price: ... }], dry_run: true|false } — row keys are the column keys. */
    public function run(Request $request, string $type, CatalogImport $import)
    {
        abort_unless(in_array($type, CatalogImport::TYPES, true), 404);
        abort_unless($request->user()->canAccess($type === 'stock' ? 'inventory' : 'catalog'), 403, 'Your account cannot import this.');

        $data = $request->validate([
            'rows'    => ['required', 'array', 'min:1', 'max:2000'],
            'rows.*'  => ['array'],
            'dry_run' => ['boolean'],
        ], ['rows.max' => 'Import up to 2,000 rows at a time. Split the file into smaller ones.']);

        return response()->json($import->run($type, $data['rows'], (bool) ($data['dry_run'] ?? false), $request->user()->id));
    }
}
