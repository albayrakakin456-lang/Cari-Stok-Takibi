<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->with('tokenable:id,name,email')
            ->latest()
            ->get();

        return view('api-tokens.index', compact('tokens', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'in:api:read,api:write'],
        ]);

        $abilities = array_values(array_unique($validated['abilities']));

        // Yazma işlemlerinde cevapları ve ilgili kayıtları okuyabilmek için okuma yetkisi de gerekir.
        if (in_array('api:write', $abilities, true) && ! in_array('api:read', $abilities, true)) {
            $abilities[] = 'api:read';
        }

        $tokenOwner = User::query()->findOrFail($validated['user_id']);
        $token = $tokenOwner->createToken($validated['name'], $abilities);

        return redirect()
            ->route('api-tokens.index')
            ->with('created_api_token', $token->plainTextToken)
            ->with('success', 'API tokenı oluşturuldu. Tokenı şimdi kopyalayın; tekrar gösterilmeyecektir.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        /** @var PersonalAccessToken $personalAccessToken */
        $personalAccessToken = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->findOrFail($token);
        $personalAccessToken->delete();

        return redirect()
            ->route('api-tokens.index')
            ->with('success', 'API tokenı iptal edildi. Bu token artık kullanılamaz.');
    }
}
