<div class="campaign-parameters d-none" data-campaign-type="conditional">
    <div class="alert alert-primary border-0 mb-4">
        <i class="bi bi-diagram-3 me-1"></i>
        Sepetteki ürün veya kategori koşulları sağlandığında aşağıdaki ödül uygulanır.
    </div>

    <div id="conditionalConditionList" class="vstack gap-3">
        @foreach($conditionalConditions as $index => $condition)
            @include('campaigns._conditional_rule', ['index' => $index, 'condition' => $condition])
        @endforeach
    </div>

    <button type="button" id="addConditionalCondition" class="btn btn-outline-primary w-100 mt-3" disabled>
        <i class="bi bi-plus-lg me-1"></i> Kural Ekle
    </button>

    <hr class="my-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-gift me-1"></i> Ödül</h6>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">İndirim Türü *</label>
            <select name="parameters[reward_type]" class="form-select" disabled>
                <option value="percentage" @selected(($parameters['reward_type'] ?? 'percentage') === 'percentage')>Yüzde indirim</option>
                <option value="fixed" @selected(($parameters['reward_type'] ?? '') === 'fixed')>Sabit tutar indirimi</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">İndirim Değeri *</label>
            <input type="number" name="parameters[reward_value]" value="{{ $parameters['reward_value'] ?? '' }}" class="form-control" min="0.01" step="0.01" placeholder="Örn: 10" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">İndirim Nereye Uygulansın? *</label>
            <select name="parameters[reward_scope]" id="conditionalRewardScope" class="form-select" disabled>
                <option value="matching_items" @selected(($parameters['reward_scope'] ?? 'matching_items') === 'matching_items')>Koşulla eşleşen ürünlere</option>
                <option value="selected_products" @selected(($parameters['reward_scope'] ?? '') === 'selected_products')>Belirli ürünlere</option>
                <option value="selected_categories" @selected(($parameters['reward_scope'] ?? '') === 'selected_categories')>Belirli kategorilere</option>
            </select>
            <div class="form-text">
                Seçilen ödül ürünleri/kategorileri sepette yoksa koşul sağlansa bile indirim oluşmaz.
            </div>
        </div>
        <div class="col-12">
            @php($rewardTargets = array_map('strval', $parameters['reward_target_ids'] ?? []))
            <select name="parameters[reward_target_ids][]" class="form-select conditional-reward-target d-none" data-scope="selected_products" multiple size="5" disabled>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(in_array((string) $product->id, $rewardTargets, true))>{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
            <select name="parameters[reward_target_ids][]" class="form-select conditional-reward-target d-none" data-scope="selected_categories" multiple size="5" disabled>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(in_array((string) $category->id, $rewardTargets, true))>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <div class="form-check form-switch border rounded-3 p-3 ps-5 bg-light-subtle">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="conditionalRepeatReward"
                    name="parameters[repeat_reward]"
                    value="1"
                    @checked((bool) ($parameters['repeat_reward'] ?? false))
                    disabled
                >
                <label class="form-check-label fw-semibold" for="conditionalRepeatReward">
                    Eşik katlandıkça indirimi artır
                </label>
                <div class="form-text">
                    Kapalı olduğunda kampanya ödülü bir kez verilir. Açık olduğunda eşik kaç kat sağlanırsa indirim de o kadar artar.
                    Örneğin 3 adet eşiğinde 100 TL indirim varsa, 6 adet için 200 TL uygulanır. Yüzde ödüller en fazla %100 olabilir.
                </div>
            </div>
        </div>
    </div>

    <template id="conditionalConditionTemplate">
        @include('campaigns._conditional_rule', [
            'index' => '__INDEX__',
            'condition' => ['source_type' => 'category', 'target_id' => '', 'minimum_quantity' => '', 'minimum_amount' => ''],
        ])
    </template>
</div>
