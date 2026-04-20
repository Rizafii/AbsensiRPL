<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFonnteSettingRequest;
use App\Models\FonnteAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FonnteSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.fonnte', [
            'checkInAccount' => $this->resolveAccount(
                FonnteAccount::EVENT_CHECK_IN,
                'Fonnte Masuk'
            ),
            'checkOutAccount' => $this->resolveAccount(
                FonnteAccount::EVENT_CHECK_OUT,
                'Fonnte Pulang'
            ),
        ]);
    }

    public function update(UpdateFonnteSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->persistAccount(
            eventType: FonnteAccount::EVENT_CHECK_IN,
            defaultAccountName: 'Fonnte Masuk',
            accountName: $validated['check_in_account_name'] ?? null,
            baseUrl: $validated['check_in_base_url'],
            token: $validated['check_in_token'] ?? null,
            parentGroupTarget: $validated['check_in_parent_group_target'] ?? null,
            timeout: (int) $validated['check_in_timeout'],
            isActive: $request->boolean('check_in_is_active'),
        );

        $this->persistAccount(
            eventType: FonnteAccount::EVENT_CHECK_OUT,
            defaultAccountName: 'Fonnte Pulang',
            accountName: $validated['check_out_account_name'] ?? null,
            baseUrl: $validated['check_out_base_url'],
            token: $validated['check_out_token'] ?? null,
            parentGroupTarget: $validated['check_out_parent_group_target'] ?? null,
            timeout: (int) $validated['check_out_timeout'],
            isActive: $request->boolean('check_out_is_active'),
        );

        return redirect()
            ->route('settings.fonnte.edit')
            ->with('status', 'Pengaturan akun Fonnte berhasil diperbarui.');
    }

    private function resolveAccount(string $eventType, string $defaultAccountName): FonnteAccount
    {
        return FonnteAccount::query()->firstOrCreate(
            ['event_type' => $eventType],
            [
                'account_name' => $defaultAccountName,
                'base_url' => 'https://api.fonnte.com',
                'timeout' => 10,
                'is_active' => true,
            ],
        );
    }

    private function persistAccount(
        string $eventType,
        string $defaultAccountName,
        ?string $accountName,
        string $baseUrl,
        ?string $token,
        ?string $parentGroupTarget,
        int $timeout,
        bool $isActive,
    ): void {
        $account = $this->resolveAccount($eventType, $defaultAccountName);

        $account->update([
            'account_name' => $this->nullableTrim($accountName) ?? $defaultAccountName,
            'base_url' => rtrim($baseUrl, '/'),
            'token' => $this->nullableTrim($token),
            'parent_group_target' => $this->nullableTrim($parentGroupTarget),
            'timeout' => $timeout,
            'is_active' => $isActive,
        ]);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
