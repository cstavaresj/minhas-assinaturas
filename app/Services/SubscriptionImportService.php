<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Category;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SubscriptionImportService
{
    /**
     * Check if a subscription already exists for a user (by privacy token).
     */
    public function isDuplicate(string $privacyToken, string $name, float $amount, $nextBillingDate): bool
    {
        $normalizedName = Str::slug($name);

        return Subscription::where('privacy_token', $privacyToken)
            ->get()
            ->contains(function ($sub) use ($normalizedName) {
                return Str::slug($sub->name) === $normalizedName;
            });
    }

    /**
     * Process a single CSV row and import it if valid.
     */
    public function importRow(string $privacyToken, array $row, bool $ignoreDuplicates = true)
    {
        $colsCount = count($row);
        if ($colsCount < 2) {
            return ['status' => 'skipped', 'reason' => 'insufficient_columns'];
        }

        $indices = $this->detectIndices($row);
        
        $name = $this->sanitizeText($this->getValue($row, $indices['name']));
        if (!$name) {
            return ['status' => 'skipped', 'reason' => 'empty_name'];
        }

        $amount = (float) str_replace(',', '.', $this->getValue($row, $indices['amount'], '0'));
        
        $startDate = $this->parseDate($this->getValue($row, $indices['start_date'])) ?: now();
        $nextDate = $this->parseDate($this->getValue($row, $indices['next_billing_date'])) ?: $startDate->copy()->addMonth();

        if ($ignoreDuplicates && $this->isDuplicate($privacyToken, $name, $amount, $nextDate)) {
            return ['status' => 'skipped', 'reason' => 'duplicate', 'name' => $name];
        }

        $categoryId = $this->resolveCategory($privacyToken, $this->sanitizeText($this->getValue($row, $indices['category'])));
        
        $status = $this->parseStatus($this->getValue($row, $indices['status'], 'active'));
        $autoRenew = $this->parseAutoRenew($this->getValue($row, $indices['auto_renew'], 'sim'));

        $subscription = Subscription::create([
            'privacy_token' => $privacyToken,
            'category_id' => $categoryId,
            'name' => mb_substr($name, 0, 80),
            'service_url' => $this->sanitizeUrl($this->getValue($row, $indices['url'])),
            'amount' => $amount,
            'currency' => $this->parseCurrency($this->getValue($row, $indices['currency'], 'BRL')),
            'billing_cycle' => $this->parseBillingCycle($this->getValue($row, $indices['billing_cycle'], 'monthly')),
            'custom_cycle_interval' => isset($indices['custom_interval']) ? (int) $this->getValue($row, $indices['custom_interval']) : null,
            'custom_cycle_period' => isset($indices['custom_period']) ? $this->getValue($row, $indices['custom_period']) : null,
            'start_date' => $startDate,
            'next_billing_date' => $nextDate,
            'status' => $status,
            'cancelled_at' => $status === 'cancelled' ? $nextDate : null,
            'auto_renew' => $autoRenew,
            'notes' => mb_substr((string) ($this->sanitizeText($this->getValue($row, $indices['notes'])) ?? ''), 0, 255) ?: null,
        ]);

        return ['status' => 'imported', 'id' => $subscription->id, 'name' => $name];
    }

    private function getValue(array $row, int $index, mixed $default = null): mixed
    {
        if ($index === -1 || !isset($row[$index])) {
            return $default;
        }
        return $row[$index];
    }

    private function detectIndices(array $row): array
    {
        $count = count($row);
        
        // Default indices for full format (13 columns)
        if ($count >= 13) {
            return [
                'name' => 0,
                'url' => 1,
                'amount' => 2,
                'currency' => 3,
                'billing_cycle' => 4,
                'custom_interval' => 5,
                'custom_period' => 6,
                'category' => 7,
                'start_date' => 8,
                'next_billing_date' => 9,
                'status' => 10,
                'auto_renew' => 11,
                'notes' => 12,
            ];
        }

        // Default indices for medium format (approx 10 columns)
        if ($count >= 10) {
            $indices = [
                'name' => 0,
                'amount' => 1,
                'currency' => 2,
                'billing_cycle' => 3,
                'category' => 4,
                'start_date' => 5,
                'next_billing_date' => 6,
                'status' => 7,
                'auto_renew' => 8,
                'notes' => 9,
                'url' => -1,
            ];
        } else {
            // Basic format: Nome, Valor, Ciclo, ...
            $indices = [
                'name' => 0,
                'url' => -1,
                'amount' => 1,
                'currency' => -1,
                'billing_cycle' => 2,
                'category' => 3,
                'start_date' => 4,
                'next_billing_date' => 5,
                'status' => 6,
                'auto_renew' => 7,
                'notes' => 8,
            ];
        }

        // URL Heuristic: if column 1 looks like a URL, shift other columns
        $candidate = trim((string) ($row[1] ?? ''));
        if ($candidate !== '' && preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $candidate) === 1) {
            $indices['url'] = 1;
            $indices['amount'] = 2;
            $indices['currency'] = ($count >= 10) ? 3 : -1;
            $indices['billing_cycle'] = ($count >= 10) ? 4 : 3;
            $indices['category'] = ($count >= 10) ? 5 : 4;
            $indices['start_date'] = ($count >= 10) ? 6 : 5;
            $indices['next_billing_date'] = ($count >= 10) ? 7 : 6;
            $indices['status'] = ($count >= 10) ? 8 : 7;
            $indices['auto_renew'] = ($count >= 10) ? 9 : 8;
            $indices['notes'] = ($count >= 10) ? 10 : 9;
        }

        return $indices;
    }

    private function sanitizeText(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        if ($value === '') return null;
        if (preg_match('/^[=+\-@]/', $value) === 1) return "'" . $value;
        return $value;
    }

    private function sanitizeUrl(?string $value): ?string
    {
        $value = $this->sanitizeText($value);
        if (!$value) return null;
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) return null;
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $value : null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', trim($value));
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    private function parseStatus(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            'active', 'ativo' => 'active',
            'paused', 'pausado' => 'paused',
            'cancelled', 'cancelada', 'inativa', 'inactive' => 'cancelled',
            default => 'active',
        };
    }

    private function parseAutoRenew(string $value): bool
    {
        $value = strtolower(trim($value));
        return !in_array($value, ['não', 'nao', 'no', 'false', '0'], true);
    }

    private function parseCurrency(string $value): string
    {
        $value = strtoupper(trim($value));
        return (preg_match('/^[A-Z]{3}$/', $value) === 1) ? $value : 'BRL';
    }

    private function parseBillingCycle(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['monthly', 'yearly', 'quarterly', 'semiannual', 'custom'], true) ? $value : 'monthly';
    }

    private function resolveCategory(string $token, ?string $categoryName): ?int
    {
        if (!$categoryName || in_array($categoryName, ['Sem categoria', ''])) {
            return null;
        }

        $category = Category::where(function($q) use ($token) {
                $q->where('privacy_token', $token)->orWhere('is_system', true);
            })
            ->where('name', $categoryName)
            ->first();

        if (!$category) {
            $category = Category::create([
                'privacy_token' => $token,
                'name' => $categoryName,
                'slug' => Str::slug($categoryName . '-' . uniqid()),
                'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                'icon' => 'bi-tag',
                'is_system' => false,
            ]);
        }

        return $category->id;
    }
}
