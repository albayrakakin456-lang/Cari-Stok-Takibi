@extends('layouts.app')

@section('title', 'Cari Listesi - Cari & Stok Takip')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">{{ __('Contacts (Customers & Suppliers) List') }}</h2>
        </div>
        <a href="{{ route('contacts.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-person-plus-fill"></i>
            <span>{{ __('Add New Contact') }}</span>
        </a>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>{{ __('Contact Name / Title') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Current Balance') }}</th>
                            <th class="text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $contact)
                        <tr>
                            <td><span class="text-muted small fw-semibold">#{{ $contact->id }}</span></td>
                            <td>
                                <strong class="text-dark">{{ $contact->name }}</strong>
                                @if($contact->email)
                                    <span class="d-block text-muted small">{{ $contact->email }}</span>
                                @endif
                            </td>
                            <td>
                                @if($contact->type == 'customer')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-person me-1"></i> {{ __('Customer') }}
                                    </span>
                                @elseif($contact->type == 'supplier')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-truck me-1"></i> {{ __('Supplier') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                                        {{ __('Customer/Supplier') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $contact->phone ?? '-' }}</td>
                            <td>
                                <span class="fw-bold fs-6 {{ $contact->balance > 0 ? 'text-success' : ($contact->balance < 0 ? 'text-danger' : 'text-muted') }}">
                                    ₺{{ number_format($contact->balance, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('contacts.show', $contact->id) }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>{{ __('Statement / Details') }}</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                {{ __('No contacts registered yet.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection