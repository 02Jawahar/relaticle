<?php

declare(strict_types=1);

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Note;
use App\Models\People;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\ImportWizard\Enums\ImportEntityType;
use Relaticle\ImportWizard\Enums\ImportStatus;
use Relaticle\ImportWizard\Models\Import;
use Relaticle\SystemAdmin\Filament\Resources\CompanyResource\Pages\ListCompanies;
use Relaticle\SystemAdmin\Filament\Resources\DealResource\Pages\ListDeals;
use Relaticle\SystemAdmin\Filament\Resources\ImportResource\Pages\ListImports;
use Relaticle\SystemAdmin\Filament\Resources\NoteResource\Pages\ListNotes;
use Relaticle\SystemAdmin\Filament\Resources\PeopleResource\Pages\ListPeople;
use Relaticle\SystemAdmin\Filament\Resources\TaskResource\Pages\ListTasks;
use Relaticle\SystemAdmin\Filament\Resources\TeamResource\Pages\ListTeams;
use Relaticle\SystemAdmin\Filament\Resources\UserResource\Pages\CreateUser;
use Relaticle\SystemAdmin\Filament\Resources\UserResource\Pages\EditUser;
use Relaticle\SystemAdmin\Filament\Resources\UserResource\Pages\ListUsers;
use Relaticle\SystemAdmin\Models\SystemAdministrator;

mutates(User::class, Team::class, Company::class, People::class, Task::class, Note::class, Deal::class, CreateUser::class, EditUser::class);

beforeEach(function () {
    $this->admin = SystemAdministrator::factory()->create();
    $this->actingAs($this->admin, 'sysadmin');
    Filament::setCurrentPanel('sysadmin');

    $this->teamOwner = User::factory()->withTeam()->create();
    $this->team = $this->teamOwner->currentTeam;
});

it('can render the users list page', function () {
    $users = User::factory(3)->withTeam()->create();

    livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('can render the teams list page', function () {
    $teams = Team::factory(3)->create();

    livewire(ListTeams::class)
        ->assertOk()
        ->assertCanSeeTableRecords($teams);
});

it('can render the companies list page', function () {
    $companies = Company::withoutEvents(fn () => Company::factory(3)
        ->for($this->team)
        ->create(['creator_id' => $this->teamOwner->id]));

    livewire(ListCompanies::class)
        ->assertOk()
        ->assertCanSeeTableRecords($companies);
});

it('can render the people list page', function () {
    $people = People::withoutEvents(fn () => People::factory(3)
        ->for($this->team)
        ->create());

    livewire(ListPeople::class)
        ->assertOk()
        ->assertCanSeeTableRecords($people);
});

it('can render the tasks list page', function () {
    $tasks = Task::withoutEvents(fn () => Task::factory(3)
        ->for($this->team)
        ->create(['creator_id' => $this->teamOwner->id]));

    livewire(ListTasks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($tasks);
});

it('can render the notes list page', function () {
    $notes = Note::withoutEvents(fn () => Note::factory(3)
        ->for($this->team)
        ->create());

    livewire(ListNotes::class)
        ->assertOk()
        ->assertCanSeeTableRecords($notes);
});

it('can render the deals list page', function () {
    $deals = Deal::withoutEvents(fn () => Deal::factory(3)
        ->for($this->team)
        ->create());

    livewire(ListDeals::class)
        ->assertOk()
        ->assertCanSeeTableRecords($deals);
});

it('can render the imports list page', function () {
    $imports = collect(range(1, 3))->map(fn () => Import::create([
        'team_id' => $this->team->id,
        'user_id' => $this->teamOwner->id,
        'entity_type' => ImportEntityType::Company,
        'file_name' => 'test.csv',
        'status' => ImportStatus::Completed,
        'total_rows' => 10,
        'created_rows' => 8,
        'failed_rows' => 2,
        'headers' => ['name', 'email'],
        'column_mappings' => [],
    ]));

    livewire(ListImports::class)
        ->assertOk()
        ->assertCanSeeTableRecords($imports);
});

it('has trashed filter on soft-deletable resources', function (string $listPageClass) {
    livewire($listPageClass)
        ->assertTableFilterExists('trashed');
})->with([
    'companies' => ListCompanies::class,
    'people' => ListPeople::class,
    'tasks' => ListTasks::class,
    'notes' => ListNotes::class,
    'deals' => ListDeals::class,
]);

it('grants team membership when creating a user with a current team', function () {
    $team = Team::factory()->create();

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'New Member',
            'email' => 'new-member@example.com',
            'password' => 'password',
            'current_team_id' => $team->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Membership — not just current_team_id — is what Filament's tenant access
    // check requires; without it the user 404s on /app/{slug} after login.
    $this->assertDatabaseHas('team_user', [
        'team_id' => $team->id,
        'user_id' => User::query()->where('email', 'new-member@example.com')->value('id'),
        'role' => TeamRole::Editor->value,
    ]);

    $user = User::query()->where('email', 'new-member@example.com')->firstOrFail()
        ->load(['ownedTeams', 'teams']);

    expect($user->canAccessTenant($team))->toBeTrue();
});

it('backfills membership when a user assigned a current team without one is re-saved', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);

    // Reproduces the original bug: current team set, but no membership row.
    expect($user->load(['ownedTeams', 'teams'])->canAccessTenant($team))->toBeFalse();

    livewire(EditUser::class, ['record' => $user->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('team_user', [
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => TeamRole::Editor->value,
    ]);

    $fresh = User::query()->findOrFail($user->id)->load(['ownedTeams', 'teams']);

    expect($fresh->canAccessTenant($team))->toBeTrue();
});
