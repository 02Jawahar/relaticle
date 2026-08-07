<?php

declare(strict_types=1);

namespace App\Actions\Deal;

use App\Mcp\Filters\CustomFieldFilter;
use App\Mcp\Schema\CustomFieldFilterSchema;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DbBuilder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class ListDeals
{
    /**
     * @param  array<string, mixed>  $filters
     * @return CursorPaginator<int, Deal>|LengthAwarePaginator<int, Deal>
     */
    public function execute(
        User $user,
        int $perPage = 15,
        bool $useCursor = false,
        array $filters = [],
        ?int $page = null,
        ?Request $request = null,
    ): CursorPaginator|LengthAwarePaginator {
        abort_unless($user->can('viewAny', Deal::class), 403);

        $request ??= new Request(['filter' => $filters]);
        $filterSchema = new CustomFieldFilterSchema;

        $query = QueryBuilder::for(
            Deal::query()->withCustomFieldValues()->whereBelongsTo($user->currentTeam),
            $request,
        )
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('contact_id'),
                AllowedFilter::custom('custom_fields', new CustomFieldFilter('deal')),
                AllowedFilter::callback('created_after', fn (Builder $query, string $value) => $query->whereDate('deals.created_at', '>=', $value)),
                AllowedFilter::callback('created_before', fn (Builder $query, string $value) => $query->whereDate('deals.created_at', '<=', $value)),
                AllowedFilter::callback('stale_days', function (Builder $query, string $value) use ($user): void {
                    $teamId = $user->currentTeam->getKey();

                    $query->whereNotExists(
                        fn (DbBuilder $sub) => $sub->from('activity_log')
                            ->where('activity_log.team_id', $teamId)
                            ->where('activity_log.subject_type', 'deal')
                            ->whereColumn('activity_log.subject_id', 'deals.id')
                            ->where('activity_log.created_at', '>=', now()->subDays((int) $value))
                    );
                }),
            )
            ->allowedFields('id', 'name', 'company_id', 'contact_id', 'creator_id', 'created_at', 'updated_at')
            ->allowedIncludes(
                'creator', 'company', 'contact',
                AllowedInclude::count('tasksCount', 'tasks'),
                AllowedInclude::count('notesCount', 'notes'),
            )
            ->allowedSorts(
                'name', 'created_at', 'updated_at',
                ...$filterSchema->allowedSorts($user, 'deal'),
            )
            ->defaultSort('-created_at')
            ->orderBy('id');

        if ($useCursor) {
            return $query->cursorPaginate($perPage);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
