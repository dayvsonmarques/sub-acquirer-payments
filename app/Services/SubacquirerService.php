<?php

namespace App\Services;

use App\Contracts\SubacquirerInterface;
use App\Models\Subacquirer;
use App\Services\Subacquirers\GenericSubacquirer;
use Illuminate\Support\Facades\Log;

class SubacquirerService
{
    public function getImplementation(Subacquirer $subacquirer): SubacquirerInterface
    {
        return new GenericSubacquirer($subacquirer);
    }

    public function getByCode(string $code): ?Subacquirer
    {
        return Subacquirer::where('code', strtolower($code))
            ->where('is_active', true)
            ->first();
    }

    public function getImplementationByCode(string $code): ?SubacquirerInterface
    {
        $subacquirer = $this->getByCode($code);

        if (!$subacquirer) {
            return null;
        }

        try {
            return $this->getImplementation($subacquirer);
        } catch (\Exception $e) {
            Log::error('Failed to get subacquirer implementation', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

