<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /** Token sahibinin carilerini güvenli alanlarla listeler. */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $contacts = $request->user()->contacts()
            ->select(['id', 'name', 'type', 'phone', 'email', 'address'])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Cariler başarıyla listelendi.',
            'data' => $contacts->items(),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    /** Token sahibi için yeni bir cari kaydı oluşturur. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:customer,supplier'],
            'phone' => ['nullable', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\s()\-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // İlişki üzerinden create kullanmak user_id'nin token sahibinden gelmesini sağlar.
        // İstemci JSON içine farklı bir user_id yazsa bile başka kullanıcı adına kayıt açamaz.
        $contact = $request->user()->contacts()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cari başarıyla oluşturuldu.',
            'data' => $contact,
        ], 201);
    }
}
