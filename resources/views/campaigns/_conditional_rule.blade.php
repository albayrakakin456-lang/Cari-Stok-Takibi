<div class="conditional-condition-row border rounded-3 p-3 bg-light-subtle">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-semibold"><i class="bi bi-funnel me-1"></i> Koşul</span>
        <button type="button" class="btn btn-sm btn-outline-danger remove-conditional-condition" title="Koşulu kaldır" disabled>
            <i class="bi bi-trash"></i>
        </button>
    </div>
    <div class="row g-3">
        <div class="col-lg-3">
            <label class="form-label small fw-semibold">Koşul Türü *</label>
            <select name="parameters[conditions][{{ $index }}][source_type]" class="form-select conditional-source-type" disabled>
                <option value="category" @selected(($condition['source_type'] ?? 'category') === 'category')>Kategori</option>
                <option value="product" @selected(($condition['source_type'] ?? '') === 'product')>Ürün</option>
            </select>
        </div>
        <div class="col-lg-3">
            <label class="form-label small fw-semibold">Hedef *</label>
            <select name="parameters[conditions][{{ $index }}][target_id]" class="form-select conditional-target" data-source="category" disabled>
                <option value="">Kategori seçin</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($condition['target_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="parameters[conditions][{{ $index }}][target_id]" class="form-select conditional-target d-none" data-source="product" disabled>
                <option value="">Ürün seçin</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) ($condition['target_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3">
            <label class="form-label small fw-semibold">Minimum Adet</label>
            <input type="number" name="parameters[conditions][{{ $index }}][minimum_quantity]" value="{{ $condition['minimum_quantity'] ?? '' }}" class="form-control" min="1" step="1" placeholder="Örn: 3" disabled>
        </div>
        <div class="col-lg-3">
            <label class="form-label small fw-semibold">Minimum Tutar (TL)</label>
            <input type="number" name="parameters[conditions][{{ $index }}][minimum_amount]" value="{{ $condition['minimum_amount'] ?? '' }}" class="form-control" min="0.01" step="0.01" placeholder="Örn: 500" disabled>
        </div>
    </div>
    <div class="form-text mt-2">Minimum adet veya minimum tutardan en az birini girin. İkisini girerseniz ikisi de sağlanmalıdır.</div>
</div>
