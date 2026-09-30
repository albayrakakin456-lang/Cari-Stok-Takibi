@php
    // Aynı partial hem ekleme hem düzenleme sayfasında kullanılır.
    $editing = isset($campaign);
    $selectedType = old('type', $editing ? $campaign->type->value : '');
    $selectedTargets = collect(old(
        'target_ids',
        $editing ? $campaign->targets->pluck('target_id')->all() : []
    ))->map(fn ($id) => (string) $id)->all();
    $parameters = old('parameters', $editing ? $campaign->parameters : []);
    $conditionalConditions = $parameters['conditions'] ?? [[
        'source_type' => 'category',
        'target_id' => '',
        'minimum_quantity' => '',
        'minimum_amount' => '',
    ]];
@endphp

@if($errors->any())
    <div class="alert alert-danger shadow-sm border-danger-subtle" role="alert" aria-live="polite">
        <div class="fw-bold mb-1">
            <i class="bi bi-exclamation-circle me-1"></i> Kampanya henüz kaydedilemedi
        </div>
        <div class="small mb-2">Lütfen aşağıdaki bilgileri tamamlayıp tekrar deneyin:</div>
        <ul class="mb-0 small ps-3">
            {{-- Aynı doğrulama mesajı birden fazla alandan gelirse kullanıcıya yalnızca bir kez gösterilir. --}}
            @foreach(collect($errors->all())->unique() as $error)
                <li class="mb-1">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" id="campaignForm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">Temel Bilgiler</div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="name" class="form-label fw-semibold">Kampanya Adı *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $campaign->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                </div>
                <div class="col-md-4">
                    <label for="code" class="form-label fw-semibold">Kampanya Kodu</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $campaign->code ?? '') }}" class="form-control @error('code') is-invalid @enderror" placeholder="Örn: YAZ2026" maxlength="100">
                </div>
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">Açıklama</label>
                    <textarea id="description" name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $campaign->description ?? '') }}</textarea>
                </div>
                <div class="col-12">
                    <label for="type" class="form-label fw-semibold">Kampanya Türü *</label>
                    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Kampanya türünü seçin</option>
                        @foreach($campaignTypes as $type)
                            <option value="{{ $type->value }}" data-target="{{ $type->targetType()?->value ?? 'conditional' }}" @selected($selectedType === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">Hedef ve İndirim Ayarları</div>
        <div class="card-body p-4">
            <div id="productTarget" class="campaign-target d-none mb-4">
                <label for="product_ids" class="form-label fw-semibold">Ürünler *</label>
                <select id="product_ids" name="target_ids[]" class="form-select" multiple size="6" disabled>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected(in_array((string) $product->id, $selectedTargets, true))>
                            {{ $product->name }} ({{ $product->code }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Birden fazla ürün seçmek için Ctrl tuşunu basılı tutabilirsiniz.</div>
            </div>

            <div id="categoryTarget" class="campaign-target d-none mb-4">
                <label for="category_ids" class="form-label fw-semibold">Kategoriler *</label>
                <select id="category_ids" name="target_ids[]" class="form-select" multiple size="5" disabled>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(in_array((string) $category->id, $selectedTargets, true))>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="product_percentage">
                <label class="form-label fw-semibold">İndirim Oranı (%) *</label>
                <input type="number" name="parameters[discount_rate]" value="{{ $parameters['discount_rate'] ?? '' }}" class="form-control" min="0.01" max="100" step="0.01" disabled>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="category_percentage">
                <label class="form-label fw-semibold">İndirim Oranı (%) *</label>
                <input type="number" name="parameters[discount_rate]" value="{{ $parameters['discount_rate'] ?? '' }}" class="form-control" min="0.01" max="100" step="0.01" disabled>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="product_fixed">
                <label class="form-label fw-semibold">Ürün Başına İndirim Tutarı (TL) *</label>
                <input type="number" name="parameters[discount_amount]" value="{{ $parameters['discount_amount'] ?? '' }}" class="form-control" min="0.01" step="0.01" disabled>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="buy_x_pay_y">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alınması Gereken Miktar (X) *</label>
                        <input type="number" name="parameters[buy_quantity]" value="{{ $parameters['buy_quantity'] ?? 3 }}" class="form-control" min="2" step="1" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ödenecek Miktar (Y) *</label>
                        <input type="number" name="parameters[pay_quantity]" value="{{ $parameters['pay_quantity'] ?? 2 }}" class="form-control" min="1" step="1" disabled>
                    </div>
                </div>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="category_third_half">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Gerekli Farklı Ürün Sayısı *</label>
                        <input type="number" name="parameters[different_product_count]" value="{{ $parameters['different_product_count'] ?? 3 }}" class="form-control" min="2" step="1" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">En Ucuz Ürüne İndirim (%) *</label>
                        <input type="number" name="parameters[discount_rate]" value="{{ $parameters['discount_rate'] ?? 50 }}" class="form-control" min="0.01" max="100" step="0.01" disabled>
                    </div>
                </div>
            </div>

            <div class="campaign-parameters d-none" data-campaign-type="category_threshold_fixed">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Minimum Kategori Tutarı (TL) *</label>
                        <input type="number" name="parameters[minimum_amount]" value="{{ $parameters['minimum_amount'] ?? 2500 }}" class="form-control" min="0.01" step="0.01" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">İndirim Tutarı (TL) *</label>
                        <input type="number" name="parameters[discount_amount]" value="{{ $parameters['discount_amount'] ?? 500 }}" class="form-control" min="0.01" step="0.01" disabled>
                    </div>
                </div>
            </div>

            @include('campaigns._conditional_fields')

            <div id="typeHint" class="text-center text-muted py-4">
                <i class="bi bi-hand-index fs-3 d-block mb-2"></i>
                Ayarları görmek için kampanya türünü seçin.
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">Yayın Ayarları</div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="starts_at" class="form-label fw-semibold">Başlangıç</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', isset($campaign) && $campaign->starts_at ? $campaign->starts_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="ends_at" class="form-label fw-semibold">Bitiş</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', isset($campaign) && $campaign->ends_at ? $campaign->ends_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
                </div>
                <div class="col-12 d-flex flex-wrap gap-4 pt-2">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $campaign->is_active ?? true))>
                        <label class="form-check-label fw-semibold" for="is_active">Kampanya aktif</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="is_exclusive" name="is_exclusive" value="1" @checked(old('is_exclusive', $campaign->is_exclusive ?? false))>
                        <label class="form-check-label fw-semibold" for="is_exclusive">Diğer kampanyalarla birleşmesin</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('campaigns.index') }}" class="btn btn-light border">İptal</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> {{ $submitLabel }}
        </button>
    </div>
</form>

@push('campaign-form-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const productTarget = document.getElementById('productTarget');
        const categoryTarget = document.getElementById('categoryTarget');
        const hint = document.getElementById('typeHint');
        const conditionList = document.getElementById('conditionalConditionList');
        const conditionTemplate = document.getElementById('conditionalConditionTemplate');
        const addConditionButton = document.getElementById('addConditionalCondition');
        const rewardScope = document.getElementById('conditionalRewardScope');

        function refreshConditionRow(row) {
            const source = row.querySelector('.conditional-source-type').value;
            row.querySelectorAll('.conditional-target').forEach(function (select) {
                const active = select.dataset.source === source;
                select.classList.toggle('d-none', ! active);
                select.disabled = ! active || typeSelect.value !== 'conditional';
            });
        }

        function refreshRewardTargets() {
            if (! rewardScope) return;

            document.querySelectorAll('.conditional-reward-target').forEach(function (select) {
                const active = select.dataset.scope === rewardScope.value;
                select.classList.toggle('d-none', ! active);
                select.disabled = ! active || typeSelect.value !== 'conditional';
            });
        }

        function refreshCampaignFields() {
            const option = typeSelect.options[typeSelect.selectedIndex];
            const type = typeSelect.value;
            const target = option?.dataset.target;

            // Önce bütün dinamik alanları gizleyip form gönderimini kapatır.
            document.querySelectorAll('.campaign-target, .campaign-parameters').forEach(function (element) {
                element.classList.add('d-none');
                element.querySelectorAll('input, select').forEach(input => input.disabled = true);
            });

            // Yalnız klasik ürün/kategori kampanyalarında standart hedef alanı açılır.
            // Koşullu kampanya kendi kural oluşturucusunu kullandığı için burada hedef açılmaz.
            const targetSections = {
                product: productTarget,
                category: categoryTarget,
            };
            const activeTarget = targetSections[target] ?? null;
            if (type && activeTarget) {
                activeTarget.classList.remove('d-none');
                activeTarget.querySelectorAll('select').forEach(select => select.disabled = false);
            }

            // Yalnızca seçili kampanya türüne ait parametreleri açar.
            const parameterSection = document.querySelector(`[data-campaign-type="${type}"]`);
            if (parameterSection) {
                parameterSection.classList.remove('d-none');
                parameterSection.querySelectorAll('input, select, textarea, button').forEach(input => input.disabled = false);
            }

            document.querySelectorAll('.conditional-condition-row').forEach(refreshConditionRow);
            refreshRewardTargets();

            hint.classList.toggle('d-none', Boolean(type));
        }

        typeSelect.addEventListener('change', refreshCampaignFields);

        conditionList?.addEventListener('change', function (event) {
            if (event.target.classList.contains('conditional-source-type')) {
                refreshConditionRow(event.target.closest('.conditional-condition-row'));
            }
        });

        conditionList?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-conditional-condition');
            if (! removeButton || conditionList.children.length === 1) return;
            removeButton.closest('.conditional-condition-row').remove();
        });

        addConditionButton?.addEventListener('click', function () {
            const index = Date.now();
            conditionList.insertAdjacentHTML('beforeend', conditionTemplate.innerHTML.replaceAll('__INDEX__', index));
            refreshConditionRow(conditionList.lastElementChild);
        });

        rewardScope?.addEventListener('change', refreshRewardTargets);
        refreshCampaignFields();
    });
</script>
@endpush

@section('scripts')
    @stack('campaign-form-script')
@endsection
