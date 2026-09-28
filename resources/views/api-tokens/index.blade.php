@extends('layouts.app')

@section('title', 'API Tokenları - Cari & Stok Takip')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-key text-primary me-2"></i>API Tokenları</h2>
        <p class="text-muted mb-0">Dış sistemlerin API ve webhook işlemlerinde kullanacağı erişim anahtarlarını yönetin.</p>
    </div>
    <a href="{{ route('integration.docs') }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
        <i class="bi bi-book me-1"></i> Entegrasyon Dokümanı
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('created_api_token'))
    <div class="alert alert-warning border-warning shadow-sm">
        <h5 class="fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Bu token yalnızca şimdi gösterilir</h5>
        <p>Karşı tarafa güvenli biçimde iletmeden önce kopyalayın. Kaybolursa mevcut token iptal edilip yenisi oluşturulmalıdır.</p>
        <div class="input-group">
            <input id="createdApiToken" type="text" readonly class="form-control font-monospace" value="{{ session('created_api_token') }}">
            <button type="button" class="btn btn-dark" onclick="copyCreatedToken(this)">
                <i class="bi bi-clipboard"></i> Kopyala
            </button>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3"><h5 class="mb-0 fw-bold">Yeni token oluştur</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('api-tokens.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="user_id" class="form-label fw-semibold">Token sahibi</label>
                        <select id="user_id" name="user_id" required class="form-select @error('user_id') is-invalid @enderror">
                            <option value="">Kullanıcı seçin...</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>
                                    {{ $user->name }} — {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">API bu kullanıcının cari, ürün ve faturalarına erişir.</div>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Token adı</label>
                        <input id="name" name="name" value="{{ old('name') }}" maxlength="100" required
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Örn: Arkadaşımın ERP sistemi">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <label class="form-label fw-semibold">Yetkiler</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="abilities[]" value="api:read" id="abilityRead" checked>
                        <label class="form-check-label" for="abilityRead"><strong>Okuma:</strong> ürün, cari, fatura ve webhook kayıtlarını görüntüler.</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="abilities[]" value="api:write" id="abilityWrite">
                        <label class="form-check-label" for="abilityWrite"><strong>Yazma:</strong> kayıt oluşturur/değiştirir ve webhook adresi yönetir.</label>
                    </div>
                    @error('abilities')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-plus-circle me-1"></i> Token Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3"><h5 class="mb-0 fw-bold">Aktif tokenlar</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Ad</th><th>Hesap</th><th>Yetkiler</th><th>Son kullanım</th><th>Oluşturulma</th><th class="text-end">İşlem</th></tr></thead>
                        <tbody>
                        @forelse ($tokens as $token)
                            <tr>
                                <td class="fw-semibold">{{ $token->name }}</td>
                                <td>
                                    <div>{{ $token->tokenable?->name ?? 'Silinmiş kullanıcı' }}</div>
                                    <small class="text-muted">{{ $token->tokenable?->email }}</small>
                                </td>
                                <td>
                                    @foreach (($token->abilities ?? []) as $ability)
                                        <span class="badge text-bg-secondary">{{ $ability }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $token->last_used_at?->format('d.m.Y H:i') ?? 'Henüz kullanılmadı' }}</td>
                                <td>{{ $token->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}"
                                          onsubmit="return confirm('Bu tokenı iptal etmek istediğinize emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-circle"></i> İptal Et</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Henüz API tokenı oluşturulmadı.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function copyCreatedToken(button) {
    const input = document.getElementById('createdApiToken');
    navigator.clipboard.writeText(input.value).then(() => {
        button.innerHTML = '<i class="bi bi-check-lg"></i> Kopyalandı';
    });
}
</script>
@endsection
