<?php

namespace App\Services;

use App\Models\EnrollRequest;

class EnrollService
{
    public function start(int $fingerprintId): EnrollRequest
    {
        return EnrollRequest::query()->create([
            'fingerprint_id' => $fingerprintId,
            'status' => 'pending',
        ]);
    }

    public function latestPending(): ?EnrollRequest
    {
        return EnrollRequest::query()
            ->pending()
            ->latest('id')
            ->first();
    }

    public function complete(int $fingerprintId): ?EnrollRequest
    {
        $request = EnrollRequest::query()
            ->pending()
            ->where('fingerprint_id', $fingerprintId)
            ->latest('id')
            ->first();

        if ($request === null) {
            return null;
        }

        $request->update([
            'status' => 'done',
        ]);

        return $request;
    }
}
