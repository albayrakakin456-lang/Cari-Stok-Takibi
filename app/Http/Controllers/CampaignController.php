<?php

namespace App\Http\Controllers;

use App\Enums\CampaignTargetType;
use App\Enums\CampaignType;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::query()
            ->with('targets')
            ->latest('id')
            ->paginate(20);

        $targets = $campaigns->getCollection()
            ->flatMap->targets;

        $productNames = Product::query()
            ->whereKey($targets
                ->filter(fn ($target) => $target->target_type === CampaignTargetType::Product)
                ->pluck('target_id'))
            ->pluck('name', 'id');

        $categoryNames = Category::query()
            ->whereKey($targets
                ->filter(fn ($target) => $target->target_type === CampaignTargetType::Category)
                ->pluck('target_id'))
            ->pluck('name', 'id');

        return view('campaigns.index', compact('campaigns', 'productNames', 'categoryNames'));
    }

    public function create(): View
    {
        return view('campaigns.create', $this->formData());
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $campaign = Campaign::create(Arr::except($validated, 'target_ids'));
            $this->replaceTargets($campaign, $validated['target_ids'] ?? []);
        });

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanya başarıyla oluşturuldu.');
    }

    public function edit(Campaign $campaign): View
    {
        $campaign->load('targets');

        return view('campaigns.edit', [
            ...$this->formData(),
            'campaign' => $campaign,
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($campaign, $validated): void {
            $campaign->update(Arr::except($validated, 'target_ids'));
            $this->replaceTargets($campaign, $validated['target_ids'] ?? []);
        });

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanya başarıyla güncellendi.');
    }

    public function toggle(Campaign $campaign): RedirectResponse
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        return back()->with(
            'success',
            $campaign->is_active ? 'Kampanya etkinleştirildi.' : 'Kampanya durduruldu.',
        );
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanya arşivlendi.');
    }

    private function formData(): array
    {
        return [
            'campaignTypes' => CampaignType::cases(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'code']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function replaceTargets(Campaign $campaign, array $targetIds): void
    {
        $targetType = $campaign->type->targetType();

        $campaign->targets()->delete();
        if ($targetType === null) {
            return;
        }

        $campaign->targets()->createMany(
            collect($targetIds)
                ->unique()
                ->map(fn (int|string $targetId) => [
                    'target_type' => $targetType,
                    'target_id' => (int) $targetId,
                ])
                ->values()
                ->all(),
        );
    }
}
