<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Point every existing currency custom field (Deal amount, Lead estimated value,
 * Order value, and any tenant-created currency field) at the Indian Rupee.
 *
 * New tenants get this from CreateTeamCustomFields. Fields created before the
 * switch stored an empty settings.additional, which the package reads as USD, so
 * this backfills them across every tenant. Only the display currency changes; the
 * stored numeric amounts are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $additional = (string) json_encode([
            'currency_code' => config('custom-fields.currency.default_code', 'INR'),
            'display_type' => 'symbol',
            'decimal_places' => 2,
        ]);

        DB::update(
            "UPDATE custom_fields
             SET settings = jsonb_set(settings::jsonb, '{additional}', ?::jsonb, true)::json,
                 updated_at = ?
             WHERE type = ?",
            [$additional, now(), 'currency'],
        );
    }
};
