@extends('layouts.app')

@section('title', 'Kampanyalar - Cari & Stok Takip')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Kampanyalar</h2>
            <p class="text-muted mb-0">Satışlarda uygulanacak indirim kurallarını yönetin.</p>
        </div>
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>Yeni Kampanya</span>
        </a>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kampanya</th>
                            <th>Tür</th>
                            <th>Hedef</th>
                            <th>Tarih</th>
                            <th class="text-center">Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $campaign->name }}</div>
                                    @if($campaign->code)
                                        <span class="stock-code-badge mt-1">{{ $campaign->code }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ $campaign->type->label() }}
                                    </span>
                                    @if($campaign->is_exclusive)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1">
                                            Birleşmez
                                        </span>
                                    @endif
                                </td>
                                <td style="min-width: 180px;">
                                    @if($campaign->type === \App\Enums\CampaignType::Conditional)
                                        <span class="badge bg-info-subtle text-info-emphasis border">
                                            Dinamik koşullara göre
                                        </span>
                                    @endif
                                    @foreach($campaign->targets->take(2) as $target)
                                        @php
                                            $targetName = $target->target_type->value === 'product'
                                                ? $productNames->get($target->target_id)
                                                : $categoryNames->get($target->target_id);
                                        @endphp
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            {{ $targetName ?? '#'.$target->target_id }}
                                        </span>
                                    @endforeach
                                    @if($campaign->targets->count() > 2)
                                        <span class="small text-muted">+{{ $campaign->targets->count() - 2 }} hedef</span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <div>{{ $campaign->starts_at?->format('d.m.Y H:i') ?? 'Hemen' }}</div>
                                    <div>{{ $campaign->ends_at?->format('d.m.Y H:i') ?? 'Süresiz' }}</div>
                                </td>
                                <td class="text-center">
                                    @if($campaign->isActiveAt())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                    @elseif($campaign->is_active)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Planlandı / Süresi Doldu</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">Pasif</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-sm btn-light border" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('campaigns.toggle', $campaign) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $campaign->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $campaign->is_active ? 'Durdur' : 'Etkinleştir' }}">
                                            <i class="bi {{ $campaign->is_active ? 'bi-pause-fill' : 'bi-play-fill' }}"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu kampanyayı arşivlemek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Arşivle">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-tags fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                        <div class="fw-semibold text-dark">Henüz kampanya yok</div>
                                        <p class="small mb-3">İlk indirim kuralınızı oluşturarak başlayın.</p>
                                        <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-lg me-1"></i> Yeni Kampanya
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($campaigns->hasPages())
        <div class="mt-4">
            {{ $campaigns->links() }}
        </div>
    @endif
@endsection
