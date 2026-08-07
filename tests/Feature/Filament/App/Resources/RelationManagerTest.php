<?php

declare(strict_types=1);

use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\CompanyResource\RelationManagers\NotesRelationManager as CompanyNotesRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\PeopleRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\TasksRelationManager as CompanyTasksRelationManager;
use App\Filament\Resources\DealResource\Pages\ViewDeal;
use App\Filament\Resources\DealResource\RelationManagers\NotesRelationManager as DealNotesRelationManager;
use App\Filament\Resources\DealResource\RelationManagers\TasksRelationManager as DealTasksRelationManager;
use App\Filament\Resources\PeopleResource\Pages\ViewPeople;
use App\Filament\Resources\PeopleResource\RelationManagers\NotesRelationManager as PeopleNotesRelationManager;
use App\Filament\Resources\PeopleResource\RelationManagers\TasksRelationManager as PeopleTasksRelationManager;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Note;
use App\Models\People;
use App\Models\Task;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Filament::setTenant($this->team);
});

/**
 * Filament renders per-row Edit/Delete actions, which authorize each record
 * through its policy. Relation managers query the relationship directly, so
 * nothing eager-loads the record's `team`. Two or more rows arm Eloquent's
 * strict lazy-loading guard (Builder::hydrate only sets it for multi-row
 * results), so a policy that resolves `$record->team` throws there while a
 * single row silently passes.
 */
it('renders the :dataset relation manager with multiple records', function (string $relationManager, Closure $setUp): void {
    [$ownerRecord, $pageClass] = $setUp($this->user, $this->team);

    livewire($relationManager, [
        'ownerRecord' => $ownerRecord,
        'pageClass' => $pageClass,
    ])->assertOk();
})->with([
    'company people' => [
        PeopleRelationManager::class,
        function (User $user, $team): array {
            $company = Company::factory()->recycle([$user, $team])->create();
            People::factory(4)->recycle([$user, $team])->create(['company_id' => $company->getKey()]);

            return [$company, ViewCompany::class];
        },
    ],
    'company notes' => [
        CompanyNotesRelationManager::class,
        function (User $user, $team): array {
            $company = Company::factory()->recycle([$user, $team])->create();
            $company->notes()->saveMany(Note::factory(4)->recycle([$user, $team])->make());

            return [$company, ViewCompany::class];
        },
    ],
    'company tasks' => [
        CompanyTasksRelationManager::class,
        function (User $user, $team): array {
            $company = Company::factory()->recycle([$user, $team])->create();
            $company->tasks()->saveMany(Task::factory(4)->recycle([$user, $team])->make());

            return [$company, ViewCompany::class];
        },
    ],
    'deal notes' => [
        DealNotesRelationManager::class,
        function (User $user, $team): array {
            $deal = Deal::factory()->recycle([$user, $team])->create();
            $deal->notes()->saveMany(Note::factory(4)->recycle([$user, $team])->make());

            return [$deal, ViewDeal::class];
        },
    ],
    'deal tasks' => [
        DealTasksRelationManager::class,
        function (User $user, $team): array {
            $deal = Deal::factory()->recycle([$user, $team])->create();
            $deal->tasks()->saveMany(Task::factory(4)->recycle([$user, $team])->make());

            return [$deal, ViewDeal::class];
        },
    ],
    'people notes' => [
        PeopleNotesRelationManager::class,
        function (User $user, $team): array {
            $people = People::factory()->recycle([$user, $team])->create();
            $people->notes()->saveMany(Note::factory(4)->recycle([$user, $team])->make());

            return [$people, ViewPeople::class];
        },
    ],
    'people tasks' => [
        PeopleTasksRelationManager::class,
        function (User $user, $team): array {
            $people = People::factory()->recycle([$user, $team])->create();
            $people->tasks()->saveMany(Task::factory(4)->recycle([$user, $team])->make());

            return [$people, ViewPeople::class];
        },
    ],
]);
