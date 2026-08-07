<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Deal\AggregateDeals;
use App\Actions\Task\NotifyTaskAssignees;
use App\Contracts\Pipeline\PipelineStage;
use App\Enums\Pipeline\DealStage;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\OrderStage;
use App\Filament\Resources\DealResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Forms\TaskForm;
use App\Models\ActivityLog\Activity;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Relaticle\Chat\Actions\ListConversations;
use Relaticle\Chat\Data\MyTaskItem;
use Relaticle\Chat\Services\MyTasksService;

final class Dashboard extends Page
{
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    public static function getNavigationLabel(): string
    {
        return __('filament/navigation.items.dashboard');
    }

    public function getTitle(): string
    {
        return __('filament/navigation.items.dashboard');
    }

    protected static ?int $navigationSort = -2;

    protected ?string $heading = '';

    protected string $view = 'chat::filament.pages.dashboard';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public ?string $recentChatTitle = null;

    public ?string $recentChatId = null;

    /**
     * Which half of the home page is showing. Held in the query string so a
     * refresh, a shared link or a back-navigation all land on the same view.
     */
    #[Url(as: 'view', keep: true)]
    public string $homeView = self::VIEW_CHAT;

    public const string VIEW_CHAT = 'chat';

    public const string VIEW_DASHBOARD = 'dashboard';

    public function setHomeView(string $view): void
    {
        $this->homeView = in_array($view, [self::VIEW_CHAT, self::VIEW_DASHBOARD], true)
            ? $view
            : self::VIEW_CHAT;
    }

    public function isDashboardView(): bool
    {
        return $this->homeView === self::VIEW_DASHBOARD;
    }

    /**
     * Headline counts and pipeline value for the dashboard view.
     *
     * @return array{leads_open: int, deals_open: int, orders_active: int, deals_won: int, pipeline_value: float}
     */
    #[Computed]
    public function pipelineStats(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $team = $user->currentTeam;

        if ($team === null) {
            return ['leads_open' => 0, 'deals_open' => 0, 'orders_active' => 0, 'deals_won' => 0, 'pipeline_value' => 0.0];
        }

        $teamId = $team->getKey();

        return [
            'leads_open' => Lead::query()->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->whereNotIn('stage', [LeadStage::WON->value, LeadStage::LOST->value])
                ->count(),
            'deals_open' => Deal::query()->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->whereNotIn('stage', [DealStage::WON->value, DealStage::LOST->value])
                ->count(),
            'deals_won' => Deal::query()->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->where('stage', DealStage::WON->value)
                ->count(),
            'orders_active' => Order::query()->withoutGlobalScopes()
                ->where('team_id', $teamId)
                ->where('stage', '!=', OrderStage::CLOSED->value)
                ->count(),
            // Reuses the same aggregate the chat and MCP surfaces report from,
            // so the dashboard cannot drift from those numbers.
            'pipeline_value' => (float) resolve(AggregateDeals::class)
                ->execute($user, 'stage')['total_amount'],
        ];
    }

    /**
     * Per-stage counts for each pipeline, in pipeline order, for the bar lists.
     *
     * @return array<string, list<array{label: string, color: string, count: int}>>
     */
    #[Computed]
    public function pipelineBreakdown(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $team = $user->currentTeam;

        if ($team === null) {
            return [];
        }

        return [
            'leads' => $this->stageCounts(Lead::class, LeadStage::cases(), $team->getKey()),
            'deals' => $this->stageCounts(Deal::class, DealStage::cases(), $team->getKey()),
            'orders' => $this->stageCounts(Order::class, OrderStage::cases(), $team->getKey()),
        ];
    }

    /**
     * Counts and drop-off across the three pipelines.
     *
     * @return list<array{label: string, count: int, total: int, rate: int|null, color: string, url: string}>
     */
    #[Computed]
    public function conversionFunnel(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $team = $user->currentTeam;

        if ($team === null) {
            return [];
        }

        $teamId = $team->getKey();

        $leads = Lead::query()->withoutGlobalScopes()->where('team_id', $teamId)->count();
        $deals = Deal::query()->withoutGlobalScopes()->where('team_id', $teamId)->count();
        $orders = Order::query()->withoutGlobalScopes()->where('team_id', $teamId)->count();

        $steps = [
            ['label' => __('filament/pages/dashboard.funnel.leads'), 'count' => $leads, 'color' => LeadStage::QUALIFIED->getColor(), 'url' => LeadResource::getUrl('board')],
            ['label' => __('filament/pages/dashboard.funnel.deals'), 'count' => $deals, 'color' => DealStage::PAYMENT->getColor(), 'url' => DealResource::getUrl('board')],
            ['label' => __('filament/pages/dashboard.funnel.orders'), 'count' => $orders, 'color' => OrderStage::DELIVERED->getColor(), 'url' => OrderResource::getUrl('board')],
        ];

        $widest = max(1, $leads, $deals, $orders);

        return array_map(function (array $step, int $index) use ($steps, $widest): array {
            $previous = $index > 0 ? $steps[$index - 1]['count'] : null;

            return [
                ...$step,
                'total' => $widest,
                // Share of the step before it, so the drop-off between pipelines
                // is visible rather than each bar being relative to the largest.
                'rate' => $previous !== null && $previous > 0
                    ? (int) round($step['count'] / $previous * 100)
                    : null,
            ];
        }, $steps, array_keys($steps));
    }

    /**
     * Deals grouped by company, highest pipeline value first.
     *
     * @return list<array{label: string, count: int, total_amount: float}>
     */
    #[Computed]
    public function topCompanies(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        if ($user->currentTeam === null) {
            return [];
        }

        $rows = resolve(AggregateDeals::class)->execute($user, 'company')['rows'];

        usort($rows, fn (array $a, array $b): int => $b['total_amount'] <=> $a['total_amount']);

        return array_slice($rows, 0, 5);
    }

    /**
     * The team's most recent activity, newest first.
     *
     * @return list<array{description: string, causer: string, subject: string, when: string}>
     */
    #[Computed]
    public function recentActivity(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $team = $user->currentTeam;

        if ($team === null) {
            return [];
        }

        // array_values, not just Collection::values(): Larastan types all() as
        // array<int, T>, which it cannot narrow to a list on its own.
        return array_values(Activity::query()
            ->withoutGlobalScopes()
            ->where('team_id', $team->getKey())
            ->with('causer')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $activity): array => [
                'description' => (string) $activity->description,
                'causer' => $activity->causer instanceof User
                    ? (string) $activity->causer->name
                    : (string) __('filament/pages/dashboard.activity.system'),
                'subject' => class_basename((string) $activity->subject_type),
                'when' => $activity->created_at?->diffForHumans(short: true) ?? '',
            ])
            ->all());
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<BackedEnum&PipelineStage>  $stages
     * @return list<array{label: string, color: string, count: int}>
     */
    private function stageCounts(string $model, array $stages, mixed $teamId): array
    {
        /** @var array<string, int> $counts */
        $counts = $model::query()->withoutGlobalScopes()
            ->where('team_id', $teamId)
            ->selectRaw('stage, count(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->all();

        return array_map(fn (BackedEnum&PipelineStage $stage): array => [
            'label' => $stage->getLabel(),
            'color' => $stage->getColor(),
            'count' => (int) ($counts[$stage->value] ?? 0),
        ], $stages);
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        $recentChat = (new ListConversations)->execute($user, 1)->first();

        if ($recentChat) {
            $this->recentChatId = $recentChat->id;
            $this->recentChatTitle = $recentChat->title;
        }
    }

    public function getGreeting(): string
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $firstName = explode(' ', $user->name)[0];

        /** @var string $timezone */
        $timezone = $user->timezone ?? config('app.timezone');
        $hour = Date::now($timezone)->hour;

        return match (true) {
            $hour < 12 => "Good morning, {$firstName}.",
            $hour < 18 => "Good afternoon, {$firstName}.",
            default => "Good evening, {$firstName}.",
        };
    }

    /**
     * @return Collection<int, MyTaskItem>
     */
    #[Computed]
    public function myTasks(): Collection
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        $team = $user->currentTeam;

        return $team
            ? resolve(MyTasksService::class)->forUser($user, $team)
            : new Collection;
    }

    public function getTasksIndexUrl(): string
    {
        return TaskResource::getUrl('index', [
            'tableFilters' => ['assigned_to_me' => ['isActive' => true]],
        ]);
    }

    public function createTaskAction(): CreateAction
    {
        return $this->configureCreateTaskAction(CreateAction::make('createTask'))
            ->label(__('filament/pages/dashboard.tasks.create_action_label'));
    }

    public function createTaskHeaderAction(): CreateAction
    {
        return $this->configureCreateTaskAction(CreateAction::make('createTaskHeader'))
            ->iconButton()
            ->color('gray')
            ->label(__('filament/pages/dashboard.tasks.create_action_label'));
    }

    private function configureCreateTaskAction(CreateAction $action): CreateAction
    {
        return $action
            ->model(Task::class)
            ->icon('heroicon-o-plus')
            ->slideOver()
            ->schema(fn (Schema $schema): Schema => TaskForm::get($schema))
            ->after(function (Task $record): void {
                resolve(NotifyTaskAssignees::class)->execute($record);
            });
    }
}
