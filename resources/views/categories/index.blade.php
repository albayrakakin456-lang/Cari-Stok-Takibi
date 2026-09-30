@extends('layouts.app')

@section('title', 'Kategoriler - Cari & Stok Takip')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Kategoriler</h2>
            <p class="text-muted mb-0">Kategorileri oluşturun ve mevcut ürünleri kategorilere bağlayın.</p>
        </div>
        <a href="{{ route('campaigns.create') }}" class="btn btn-outline-primary"><i class="bi bi-tags me-1"></i> Kampanya Oluştur</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">Yeni Kategori</div>
        <div class="card-body">
            <form action="{{ route('categories.store') }}" method="POST" class="d-flex gap-2">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Örn: Elektronik" maxlength="255" required>
                <button type="submit" class="btn btn-primary text-nowrap"><i class="bi bi-plus-lg me-1"></i> Kategori Ekle</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @forelse($categories as $category)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="fw-bold">{{ $category->name }}</span>
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ $category->products->count() }} ürün</span>
                        </div>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Kategori silinsin mi? Ürünler silinmeyecek.')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Kategoriyi sil"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('categories.products.sync', $category) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <label class="form-label fw-semibold">Bu kategorideki ürünler</label>
                            <select name="product_ids[]" class="form-select mb-3" multiple size="7">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected($product->category_id === $category->id)>
                                        {{ $product->name }} ({{ $product->code }})
                                        @if($product->category_id && $product->category_id !== $category->id)
                                            — başka kategoride
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Ctrl ile birden fazla ürün seçebilirsiniz.</small>
                                <button type="submit" class="btn btn-primary btn-sm">Ürünleri Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">
                    <i class="bi bi-grid fs-1 d-block mb-2 opacity-50"></i>Henüz kategori yok. Yukarıdaki formdan ilk kategoriyi ekleyin.
                </div></div>
            </div>
        @endforelse
    </div>
@endsection
