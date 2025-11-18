<?php

namespace App\Contracts;

use App\Models\Subacquirer;

interface SubacquirerInterface
{
    /**
     * Get the subacquirer model instance
     */
    public function getSubacquirer(): Subacquirer;

    /**
     * Process a PIX transaction
     *
     * @param array $data Transaction data
     * @return array Response from subacquirer
     */
    public function processPix(array $data): array;

    /**
     * Process a withdraw transaction
     *
     * @param array $data Transaction data
     * @return array Response from subacquirer
     */
    public function processWithdraw(array $data): array;

    /**
     * Get the base URL for API requests
     */
    public function getBaseUrl(): string;
}

