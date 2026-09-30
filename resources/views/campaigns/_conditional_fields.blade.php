<div class="campaign-parameters d-none" data-campaign-type="conditional">
    <div class="alert alert-primary border-0 mb-4">
        <i class="bi bi-diagram-3 me-1"></i>
        {{ __('The reward below is applied when the cart conditions are met.') }}
    </div>

    <div id="conditionalConditionList" class="vstack gap-3">
        @foreach($conditionalConditions as $index => $condition)
            @include('campaigns._conditional_rule', ['index' => $index, 'condition' => $condition])
        @endforeach
    </div>

    <button type="button" id="addConditionalCondition" class="btn btn-outline-primary w-100 mt-3" disabled>
        <i class="bi bi-plus-lg me-1"></i> {{ __('Add Rule') }}
    </button>

    <hr class="my-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-gift me-1"></i> {{ __('Reward') }}</h6>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Discount Type') }} *</label>
            <select name="parameters[reward_type]" class="form-select" disabled>
                <option value="percentage" @selected(($parameters['reward_type'] ?? 'percentage') === 'percentage')>{{ __('Percentage discount') }}</option>
                <option value="fixed" @selected(($parameters['reward_type'] ?? '') === 'fixed')>{{ __('Fixed amount discount') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Discount Value') }} *</label>
            <input type="number" name="parameters[reward_value]" value="{{ $parameters['reward_value'] ?? '' }}" class="form-control" min="0.01" step="0.01" placeholder="Örn: 10" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">{{ __('Where should the discount be applied?') }} *</label>
            <select name="parameters[reward_scope]" id="conditionalRewardScope" class="form-select" disabled>
                <option value="matching_items" @selected(($parameters['reward_scope'] ?? 'matching_items') === 'matching_items')>{{ __('Items matching the rule') }}</option>
                <option value="selected_products" @selected(($parameters['reward_scope'] ?? '') === 'selected_products')>{{ __('Selected products') }}</option>
                <option value="selected_categories" @selected(($parameters['reward_scope'] ?? '') === 'selected_categories')>{{ __('Selected categories') }}</option>
            </select>
            <div class="form-text">
                {{ __('No discount is created if the selected reward products or categories are not in the cart.') }}
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
                    {{ __('Increase the discount as the threshold repeats') }}
                </label>
                <div class="form-text">
                    {{ __('When disabled, the reward is applied once. When enabled, the discount increases for each completed threshold. For example, a 100 TL reward at 3 units becomes 200 TL at 6 units. Percentage rewards cannot exceed 100%.') }}
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
