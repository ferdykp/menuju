<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Need;
use App\Models\Workspace;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $assets = $workspace->assets()
            ->withSum('allocations as allocated_quantity', 'quantity')
            ->withCount('allocations')
            ->orderBy('status')->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'assets' => $assets->getCollection()->map(fn (Asset $asset) => $this->serialize($asset)),
            'pagination' => PaginationMeta::from($assets),
            'summary' => [
                'count' => $workspace->assets()->count(),
                'estimated_value' => (float) $workspace->assets()->where('status', '!=', 'sold')->sum('estimated_value'),
            ],
            'needs' => $workspace->needs()->where('status', '!=', 'purchased')->orderBy('title')->get(['id', 'title', 'event_id']),
        ]);
    }

    public function show(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset->workspace);
        $asset->load(['allocations.need:id,workspace_id,title,status,event_id', 'allocations.need.event:id,title', 'valuations']);
        $asset->loadSum('allocations as allocated_quantity', 'quantity');

        return response()->json(['asset' => $this->serialize($asset)]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $this->validateAsset($request);
        $asset = $workspace->assets()->create($data + ['created_by' => $request->user()->id]);
        if ($asset->estimated_value !== null && $asset->valuation_date) $asset->valuations()->create(['recorded_by' => $request->user()->id, 'estimated_value' => $asset->estimated_value, 'valuation_date' => $asset->valuation_date]);

        return response()->json(['asset' => $this->serialize($asset)], 201);
    }

    public function update(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset->workspace);
        $data = $this->validateAsset($request, true);
        $changesValuation = array_key_exists('estimated_value', $data) || array_key_exists('valuation_date', $data);
        $asset->update($data);
        if ($changesValuation && $asset->estimated_value !== null && $asset->valuation_date) {
            $previous = $asset->valuations()->latest('id')->first();
            if (! $previous || (float) $previous->estimated_value !== (float) $asset->estimated_value || $previous->valuation_date->format('Y-m-d') !== $asset->valuation_date->format('Y-m-d')) {
                $asset->valuations()->create(['recorded_by' => $request->user()->id, 'estimated_value' => $asset->estimated_value, 'valuation_date' => $asset->valuation_date]);
            }
        }

        return response()->json(['asset' => $this->serialize($asset->fresh())]);
    }

    public function destroy(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset->workspace);
        abort_if($asset->allocations()->exists(), 422, 'Lepaskan alokasi aset sebelum menghapusnya.');
        $asset->delete();

        return response()->noContent();
    }

    public function allocate(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset->workspace);
        $data = $request->validate([
            'need_id' => ['required', 'integer', Rule::exists('needs', 'id')->where(fn ($query) => $query->where('workspace_id', $asset->workspace_id)->whereNull('deleted_at'))],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $allocation = DB::transaction(function () use ($request, $asset, $data) {
            $lockedAsset = Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedAsset->status === 'sold', 422, 'Aset yang sudah dijual tidak dapat dialokasikan.');
            $need = Need::query()->where('workspace_id', $asset->workspace_id)->findOrFail($data['need_id']);
            abort_if($need->status === 'purchased', 422, 'Kebutuhan yang sudah dibeli tidak dapat dipenuhi dengan aset.');
            $current = (float) $lockedAsset->allocations()->sum('quantity');
            $existing = (float) $lockedAsset->allocations()->where('need_id', $need->id)->value('quantity');
            $requested = (float) $data['quantity'];
            abort_if($current - $existing + $requested > (float) $lockedAsset->quantity + 0.0005, 422, 'Jumlah alokasi melebihi jumlah aset yang tersedia.');

            $allocation = $lockedAsset->allocations()->updateOrCreate(
                ['need_id' => $need->id],
                ['created_by' => $request->user()->id, 'quantity' => $requested, 'notes' => $data['notes'] ?? null],
            );
            $need->update(['status' => 'prepared']);
            $asset->workspace->activities()->create([
                'actor_id' => $request->user()->id,
                'action' => 'allocated',
                'subject_type' => 'Asset',
                'subject_id' => $asset->id,
                'summary' => "mengalokasikan {$requested} {$asset->unit} {$asset->name} untuk {$need->title}",
                'occurred_at' => now(),
            ]);

            return $allocation;
        });

        return response()->json(['allocation' => $allocation->load('need:id,title,status')], 201);
    }

    public function release(Request $request, Asset $asset, int $allocation)
    {
        $this->authorize('view', $asset->workspace);
        $row = $asset->allocations()->findOrFail($allocation);
        $need = $row->need;
        $row->delete();
        if ($need && ! $need->assets()->exists()) $need->update(['status' => 'planned']);
        $asset->workspace->activities()->create([
            'actor_id' => $request->user()->id,
            'action' => 'released',
            'subject_type' => 'Asset',
            'subject_id' => $asset->id,
            'summary' => "melepaskan alokasi {$asset->name}",
            'occurred_at' => now(),
        ]);

        return response()->noContent();
    }

    private function validateAsset(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:160'],
            'asset_type' => ['sometimes', Rule::in(['gold', 'jewelry', 'investment', 'other'])],
            'owner_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'quantity' => [$required, 'numeric', 'gt:0', 'max:999999999.999'],
            'unit' => ['sometimes', 'string', 'max:24'],
            'purity' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:1000'],
            'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'valuation_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'acquired_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['available', 'allocated', 'used', 'sold'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);
    }

    private function serialize(Asset $asset): array
    {
        $row = $asset->toArray();
        $row['valuation_date'] = $asset->valuation_date?->format('Y-m-d');
        $row['acquired_at'] = $asset->acquired_at?->format('Y-m-d');
        $row['allocated_quantity'] = (float) ($asset->allocated_quantity ?? $asset->allocations()->sum('quantity'));
        $row['available_quantity'] = max((float) $asset->quantity - $row['allocated_quantity'], 0);
        $row['allocations'] = $asset->relationLoaded('allocations') ? $asset->allocations->map(fn ($allocation) => [
            'id' => $allocation->id,
            'quantity' => (float) $allocation->quantity,
            'notes' => $allocation->notes,
            'need' => $allocation->need ? [
                'id' => $allocation->need->id,
                'title' => $allocation->need->title,
                'status' => $allocation->need->status,
                'event' => $allocation->need->event,
            ] : null,
        ])->values() : [];
        $row['valuations'] = $asset->relationLoaded('valuations') ? $asset->valuations->map(fn ($valuation) => [
            'id' => $valuation->id,
            'estimated_value' => (float) $valuation->estimated_value,
            'valuation_date' => $valuation->valuation_date?->format('Y-m-d'),
            'notes' => $valuation->notes,
        ])->values() : [];

        return $row;
    }
}
