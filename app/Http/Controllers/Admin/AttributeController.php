<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductSpec;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Specs (RAM, Storage, Color ...) with their values, and the categories that use them.
 */
class AttributeController extends Controller
{
    public function index()
    {
        $usage = ProductSpec::query()->selectRaw('attribute_value_id, count(distinct product_id) as total')
            ->whereNotNull('attribute_value_id')->groupBy('attribute_value_id')->pluck('total', 'attribute_value_id');

        return Attribute::query()
            ->with(['values', 'categories:id'])
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(function (Attribute $a) use ($usage) {
                $a->values->each(fn ($v) => $v->setAttribute('product_count', (int) ($usage[$v->id] ?? 0)));
                $a->setAttribute('category_ids', $a->categories->pluck('id')->values());
                $a->unsetRelation('categories');

                return $a;
            });
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $attribute = DB::transaction(function () use ($data) {
            $attribute = Attribute::create($this->fields($data) + [
                'slug'       => Slug::unique(Attribute::class, null, $data['name']),
                'sort_order' => (int) Attribute::max('sort_order') + 1,
            ]);
            $this->syncValues($attribute, $data['values'] ?? []);
            $attribute->categories()->sync($data['category_ids'] ?? []);

            return $attribute;
        });

        return response()->json($attribute->load('values'), 201);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $data = $this->validated($request, $attribute);

        DB::transaction(function () use ($attribute, $data) {
            $attribute->update($this->fields($data));
            $this->syncValues($attribute, $data['values'] ?? []);
            if (array_key_exists('category_ids', $data)) {
                $attribute->categories()->sync($data['category_ids'] ?? []);
            }
        });

        return $attribute->load('values');
    }

    /** Removing a spec also removes it from every product that had it. */
    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?Attribute $attribute = null): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:80', Rule::unique('attributes', 'name')->ignore($attribute?->id)],
            'type'             => ['required', Rule::in(Attribute::TYPES)],
            'unit'             => ['nullable', 'string', 'max:20'],
            'group'            => ['nullable', 'string', 'max:60'],
            'is_filterable'    => ['boolean'],
            'is_comparable'    => ['boolean'],
            'is_variant'       => ['boolean'],
            'values'           => ['array'],
            'values.*.id'      => ['nullable', 'integer'],
            'values.*.value'   => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'values.*.swatch'  => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'category_ids'     => ['sometimes', 'array'],
            'category_ids.*'   => ['integer', 'exists:categories,id'],
        ], [
            'values.*.value.distinct' => 'The value ":input" is listed twice.',
        ]);
    }

    private function fields(array $data): array
    {
        $select = $data['type'] === 'select';

        return [
            'name'          => $data['name'],
            'type'          => $data['type'],
            'unit'          => $data['unit'] ?? null,
            'group'         => $data['group'] ?? null,
            // Free-text specs cannot be filtered on or used for variants.
            'is_filterable' => $select && ($data['is_filterable'] ?? true),
            'is_comparable' => $data['is_comparable'] ?? true,
            'is_variant'    => $select && ($data['is_variant'] ?? false),
        ];
    }

    /** Keeps existing values (so products keep them), adds new ones, removes the ones left out. */
    private function syncValues(Attribute $attribute, array $values): void
    {
        $values = array_values($values);

        // Remove dropped values first, so their slugs are free for the ones being saved.
        $keepIds = collect($values)->pluck('id')->filter()->all();
        AttributeValue::where('attribute_id', $attribute->id)->whereNotIn('id', $keepIds)->delete();

        $used = [];
        foreach ($values as $i => $row) {
            // "1TB" and "1 TB" would share a slug; number repeats so the unique index never fails.
            $base = Str::slug($row['value']) ?: 'v' . ($i + 1);
            $slug = $base;
            for ($n = 2; isset($used[$slug]); $n++) {
                $slug = "{$base}-{$n}";
            }
            $used[$slug] = true;

            $fields = [
                'value'      => trim($row['value']),
                'slug'       => $slug,
                'swatch'     => $row['swatch'] ?? null,
                'sort_order' => $i + 1,
            ];

            $existing = ! empty($row['id']) ? $attribute->values()->whereKey($row['id'])->first() : null;
            $existing ? $existing->update($fields) : $attribute->values()->create($fields);
        }
    }
}
