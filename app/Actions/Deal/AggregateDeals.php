<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Enums\CustomFields\DealField;
use App\Enums\Pipeline\DealStage;
use App\Models\CustomField;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AggregateDeals
{
    /**
     * Cap on the number of grouped rows returned. Grand totals are computed
     * separately so they stay accurate even when groups exceed this cap.
     */
    private const int MAX_GROUPS = 100;

    /**
     * Aggregate deals by stage or company.
     *
     * @return array{group_by: string, rows: list<array{label: string, count: int, total_amount: float}>, total_count: int, total_amount: float, truncated: bool}
     */
    public function execute(
        User $user,
        string $groupBy,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        abort_unless($user->can('viewAny', Deal::class), 403);

        $teamId = $user->currentTeam->getKey();

        return match ($groupBy) {
            'stage' => $this->byStage($teamId, $dateFrom, $dateTo),
            'company' => $this->byCompany($teamId, $dateFrom, $dateTo),
            default => abort(422, "Invalid group_by value: {$groupBy}. Must be 'stage' or 'company'."),
        };
    }

    /**
     * @return array{group_by: string, rows: list<array{label: string, count: int, total_amount: float}>, total_count: int, total_amount: float, truncated: bool}
     */
    private function byStage(mixed $teamId, ?string $dateFrom, ?string $dateTo): array
    {
        $amountFieldId = $this->resolveFieldId($teamId, DealField::AMOUNT->value);

        $dateClause = $this->dateClause($dateFrom, $dateTo);
        $dateBindings = $this->dateBindings($dateFrom, $dateTo);

        $amountJoin = $amountFieldId !== null
            ? "LEFT JOIN custom_field_values amount_cfv ON amount_cfv.entity_id = o.id AND amount_cfv.entity_type = 'deal' AND amount_cfv.custom_field_id = ?"
            : '';
        $amountSelect = $amountFieldId !== null
            ? 'COALESCE(SUM(amount_cfv.float_value), 0) as total_amount'
            : '0 as total_amount';
        $amountBindings = $amountFieldId !== null ? [$amountFieldId] : [];

        $totals = $this->grandTotals($teamId, $amountFieldId, $dateFrom, $dateTo);

        $rows = DB::select(
            "SELECT o.stage as stage, COUNT(*) as count, {$amountSelect}
             FROM deals o
             {$amountJoin}
             WHERE o.team_id = ? AND o.deleted_at IS NULL{$dateClause}
             GROUP BY o.stage
             ORDER BY count DESC
             LIMIT ".self::MAX_GROUPS,
            [...$amountBindings, $teamId, ...$dateBindings],
        );

        $mappedRows = [];
        foreach ($rows as $row) {
            $stage = is_string($row->stage) ? DealStage::tryFrom($row->stage) : null;
            $mappedRows[] = [
                'label' => $stage instanceof DealStage ? $stage->getLabel() : 'Unspecified',
                'count' => (int) $row->count,
                'total_amount' => (float) $row->total_amount,
            ];
        }

        return $this->buildResult('stage', $mappedRows, $totals['count'], $totals['amount']);
    }

    /**
     * @return array{group_by: string, rows: list<array{label: string, count: int, total_amount: float}>, total_count: int, total_amount: float, truncated: bool}
     */
    private function byCompany(mixed $teamId, ?string $dateFrom, ?string $dateTo): array
    {
        $amountFieldId = $this->resolveFieldId($teamId, DealField::AMOUNT->value);

        $dateClause = $this->dateClause($dateFrom, $dateTo);
        $dateBindings = $this->dateBindings($dateFrom, $dateTo);

        $amountJoin = $amountFieldId !== null
            ? "LEFT JOIN custom_field_values amount_cfv ON amount_cfv.entity_id = o.id AND amount_cfv.entity_type = 'deal' AND amount_cfv.custom_field_id = ?"
            : '';
        $amountSelect = $amountFieldId !== null
            ? 'COALESCE(SUM(amount_cfv.float_value), 0) as total_amount'
            : '0 as total_amount';
        $amountBindings = $amountFieldId !== null ? [$amountFieldId] : [];

        $rows = DB::select(
            "SELECT COALESCE(c.name, 'No Company') as label, COUNT(*) as count, {$amountSelect}
             FROM deals o
             LEFT JOIN companies c ON c.id = o.company_id AND c.deleted_at IS NULL
             {$amountJoin}
             WHERE o.team_id = ? AND o.deleted_at IS NULL{$dateClause}
             GROUP BY c.id, c.name
             ORDER BY count DESC
             LIMIT ".self::MAX_GROUPS,
            [...$amountBindings, $teamId, ...$dateBindings],
        );

        $mappedRows = [];
        foreach ($rows as $row) {
            $mappedRows[] = [
                'label' => (string) $row->label,
                'count' => (int) $row->count,
                'total_amount' => (float) $row->total_amount,
            ];
        }

        $totals = $this->grandTotals($teamId, $amountFieldId, $dateFrom, $dateTo);

        return $this->buildResult('company', $mappedRows, $totals['count'], $totals['amount']);
    }

    /**
     * Grand totals across ALL matching deals, independent of the grouped
     * row cap, so reported counts and pipeline value stay correct when the number
     * of groups exceeds the cap.
     *
     * @return array{count: int, amount: float}
     */
    private function grandTotals(mixed $teamId, mixed $amountFieldId, ?string $dateFrom, ?string $dateTo): array
    {
        $dateClause = $this->dateClause($dateFrom, $dateTo);
        $dateBindings = $this->dateBindings($dateFrom, $dateTo);

        $amountJoin = $amountFieldId !== null
            ? "LEFT JOIN custom_field_values amount_cfv ON amount_cfv.entity_id = o.id AND amount_cfv.entity_type = 'deal' AND amount_cfv.custom_field_id = ?"
            : '';
        $amountSelect = $amountFieldId !== null
            ? 'COALESCE(SUM(amount_cfv.float_value), 0) as total_amount'
            : '0 as total_amount';
        $amountBindings = $amountFieldId !== null ? [$amountFieldId] : [];

        $row = DB::select(
            "SELECT COUNT(*) as count, {$amountSelect}
             FROM deals o
             {$amountJoin}
             WHERE o.team_id = ? AND o.deleted_at IS NULL{$dateClause}",
            [...$amountBindings, $teamId, ...$dateBindings],
        );

        return [
            'count' => (int) ($row[0]->count ?? 0),
            'amount' => (float) ($row[0]->total_amount ?? 0),
        ];
    }

    private function resolveFieldId(mixed $teamId, string $code): mixed
    {
        return CustomField::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $teamId)
            ->where('entity_type', 'deal')
            ->where('code', $code)
            ->active()
            ->value('id');
    }

    private function dateClause(?string $dateFrom, ?string $dateTo): string
    {
        $clause = '';

        if ($dateFrom !== null) {
            $clause .= ' AND o.created_at >= ?';
        }

        if ($dateTo !== null) {
            $clause .= ' AND o.created_at <= ?';
        }

        return $clause;
    }

    /**
     * @return list<string>
     */
    private function dateBindings(?string $dateFrom, ?string $dateTo): array
    {
        $bindings = [];

        if ($dateFrom !== null) {
            $bindings[] = $dateFrom;
        }

        if ($dateTo !== null) {
            $bindings[] = $dateTo.' 23:59:59';
        }

        return $bindings;
    }

    /**
     * @param  list<array{label: string, count: int, total_amount: float}>  $rows
     * @return array{group_by: string, rows: list<array{label: string, count: int, total_amount: float}>, total_count: int, total_amount: float, truncated: bool}
     */
    private function buildResult(string $groupBy, array $rows, int $totalCount, float $totalAmount): array
    {
        return [
            'group_by' => $groupBy,
            'rows' => $rows,
            'total_count' => $totalCount,
            'total_amount' => $totalAmount,
            'truncated' => count($rows) >= self::MAX_GROUPS,
        ];
    }
}
