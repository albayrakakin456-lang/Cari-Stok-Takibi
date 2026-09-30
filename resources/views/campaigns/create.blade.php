@extends('layouts.app')

@section('title', 'Yeni Kampanya - Cari & Stok Takip')

@section('content')
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('campaigns.index') }}" class="btn btn-light border" title="Geri">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="fw-bold text-dark mb-1">Yeni Kampanya</h2>
            <p class="text-muted mb-0">Kampanya türünü ve uygulanacağı hedefleri belirleyin.</p>
        </div>
    </div>

    @include('campaigns._form', [
        'action' => route('campaigns.store'),
        'method' => 'POST',
        'submitLabel' => 'Kampanyayı Oluştur',
    ])
@endsection
