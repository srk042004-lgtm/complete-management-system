<?php

namespace App\Http\Controllers\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\BookingFoodPackage;
use App\Models\FoodCategory;
use App\Models\HotelOperationLog;
use App\Models\MenuItem;
use App\Support\HotelExtraServiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoodCatalogController extends Controller
{
    private const DEFAULT_CATEGORIES = [
        ['Food', 'utensils', 10],
        ['Milkshakes', 'milkshake', 20],
        ['Fast Food', 'burger', 30],
        ['Beverages', 'coffee', 40],
        ['Desserts', 'cake', 50],
    ];

    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->tenant_id;
        foreach (self::DEFAULT_CATEGORIES as [$name, $icon, $sortOrder]) {
            FoodCategory::query()->firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $name],
                ['icon' => $icon, 'sort_order' => $sortOrder],
            );
        }
        app(HotelExtraServiceCatalog::class)->ensureForTenant($tenantId);

        $categories = FoodCategory::query()
            ->where('tenant_id', $tenantId)
            ->withCount('items')
            ->with(['items' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontdesk.food-catalog', compact('categories'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('food_categories', 'name')->where('tenant_id', $tenantId)],
            'icon' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $category = DB::transaction(function () use ($request, $tenantId, $validated): FoodCategory {
            $category = FoodCategory::query()->create(['tenant_id' => $tenantId] + $validated);
            $this->audit($request, 'food_category.created', $category::class, (int) $category->id, ['name' => $category->name], $tenantId);

            return $category;
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', "Menu category {$category->name} was added.");
    }

    public function updateCategory(Request $request, int $categoryId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $category = FoodCategory::query()->where('tenant_id', $tenantId)->findOrFail($categoryId);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('food_categories', 'name')->where('tenant_id', $tenantId)->ignore($categoryId)],
            'icon' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        DB::transaction(function () use ($request, $tenantId, $category, $validated): void {
            $category->update($validated);
            $this->audit($request, 'food_category.updated', $category::class, (int) $category->id, [
                'name' => $category->name,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', 'Menu category updated.');
    }

    public function deleteCategory(Request $request, int $categoryId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $category = FoodCategory::query()->where('tenant_id', $tenantId)->findOrFail($categoryId);
        if ($category->items()->exists()) {
            return back()->withErrors(['category' => 'Delete or reassign this category’s menu items before removing the category.']);
        }

        DB::transaction(function () use ($request, $tenantId, $category): void {
            $categoryId = (int) $category->id;
            $categoryName = $category->name;
            BookingFoodPackage::query()
                ->where('tenant_id', $tenantId)
                ->where('food_category_id', $categoryId)
                ->update(['food_category_id' => null]);
            $category->delete();
            $this->audit($request, 'food_category.deleted', FoodCategory::class, $categoryId, [
                'name' => $categoryName,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', 'Menu category deleted.');
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $validated = $request->validate([
            'food_category_id' => ['required', Rule::exists('food_categories', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'billing_type' => ['required', Rule::in(['per_guest_per_night', 'one_time_order'])],
            'is_available' => ['required', 'boolean'],
        ]);

        $category = FoodCategory::query()->where('tenant_id', $tenantId)->findOrFail($validated['food_category_id']);
        $request->validate([
            'name' => [Rule::unique('menu_items', 'name')->where('food_category_id', $category->id)],
        ]);
        $item = DB::transaction(function () use ($request, $validated, $tenantId): MenuItem {
            $item = MenuItem::query()->create(['tenant_id' => $tenantId] + $validated);
            $this->audit($request, 'menu_item.created', $item::class, (int) $item->id, [
                'name' => $item->name,
                'category_id' => $item->food_category_id,
            ], $tenantId);

            return $item;
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', "Menu item {$item->name} was added.");
    }

    public function updateItem(Request $request, int $itemId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $item = MenuItem::query()
            ->whereHas('category', fn ($query) => $query->where('tenant_id', $tenantId))
            ->findOrFail($itemId);
        $validated = $request->validate([
            'food_category_id' => ['required', Rule::exists('food_categories', 'id')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'billing_type' => ['required', Rule::in(['per_guest_per_night', 'one_time_order'])],
            'is_available' => ['required', 'boolean'],
        ]);
        $request->validate([
            'name' => [Rule::unique('menu_items', 'name')->where('food_category_id', $validated['food_category_id'])->ignore($itemId)],
        ]);

        DB::transaction(function () use ($request, $tenantId, $item, $validated): void {
            $item->update($validated);
            $this->audit($request, 'menu_item.updated', $item::class, (int) $item->id, [
                'name' => $item->name,
                'category_id' => $item->food_category_id,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', 'Menu item updated.');
    }

    public function deleteItem(Request $request, int $itemId): RedirectResponse
    {
        $tenantId = (int) $request->user()->tenant_id;
        $item = MenuItem::query()
            ->whereHas('category', fn ($query) => $query->where('tenant_id', $tenantId))
            ->findOrFail($itemId);

        DB::transaction(function () use ($request, $tenantId, $item): void {
            $itemId = (int) $item->id;
            BookingFoodPackage::query()
                ->where('tenant_id', $tenantId)
                ->where('menu_item_id', $itemId)
                ->update(['menu_item_id' => null]);
            $item->delete();
            $this->audit($request, 'menu_item.deleted', MenuItem::class, $itemId, [
                'name' => $item->name,
            ], $tenantId);
        });

        return redirect()->route('frontdesk.food-catalog')->with('success', 'Menu item deleted.');
    }

    private function audit(Request $request, string $action, string $subjectType, int $subjectId, array $details, int $tenantId): void
    {
        HotelOperationLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'details' => $details,
            'ip_address' => $request->ip(),
        ]);
    }
}
