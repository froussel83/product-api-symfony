<?php

namespace App\Service;

final class SkuGenerator
{
    /**
     * Format: PROD-{first4}-{random7hex}
     * ex: PROD-MACB-a8d3f1c
     */
    public function generate(string $productName): string
    {
        $first = mb_strtoupper(mb_substr(trim($productName), 0, 4));
        $rand  = bin2hex(random_bytes(4));
        return sprintf('PROD-%s-%s', $first, substr($rand, 0, 7));
    }
}
