<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        return view('tokens.index', [
            'tokens' => auth()->user()->tokens()->latest()->get(),
        ]);
    }

    public function store(CreateApiTokenRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->string('name')->toString(), ['workspace-api']);

        return back()->with('status', 'Token created. Copy it now: '.$token->plainTextToken);
    }

    public function destroy(int $tokenId): RedirectResponse
    {
        auth()->user()->tokens()->whereKey($tokenId)->delete();

        return back()->with('status', 'Token revoked.');
    }
}
