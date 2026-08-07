<?php

declare(strict_types=1);

use App\Actions\Note\CreateNoteForRecord;
use App\Actions\Task\CreateTaskForRecord;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(CreateNoteForRecord::class, CreateTaskForRecord::class);

beforeEach(function (): void {
    $this->user = User::factory()->withTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

dataset('pipeline records', [
    'lead' => [fn (): Lead => Lead::factory()->recycle([test()->user, test()->team])->create()],
    'deal' => [fn (): Deal => Deal::factory()->recycle([test()->user, test()->team])->create()],
    'order' => [fn (): Order => Order::factory()->recycle([test()->user, test()->team])->create()],
]);

it('adds a note to a :dataset and links it to that record', function (Closure $make): void {
    $record = $make();

    $note = resolve(CreateNoteForRecord::class)->execute($this->user, $record, [
        'title' => 'Site survey booked',
    ]);

    expect($note->title)->toBe('Site survey booked')
        ->and($note->team_id)->toBe($this->team->getKey())
        ->and($record->notes()->pluck('notes.id'))->toContain($note->getKey());
})->with('pipeline records');

it('adds a task to a :dataset and links it to that record', function (Closure $make): void {
    $record = $make();

    $task = resolve(CreateTaskForRecord::class)->execute($this->user, $record, [
        'title' => 'Send revised quotation',
    ]);

    expect($task->title)->toBe('Send revised quotation')
        ->and($record->tasks()->pluck('tasks.id'))->toContain($task->getKey());
})->with('pipeline records');

it('keeps notes attached to the record they were added from', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])->create();
    $other = Lead::factory()->recycle([$this->user, $this->team])->create();

    resolve(CreateNoteForRecord::class)->execute($this->user, $lead, ['title' => 'Only for this lead']);

    expect($lead->notes()->count())->toBe(1)
        ->and($other->notes()->count())->toBe(0);
});

it('denies adding a note to a record in another team', function (): void {
    $outsider = User::factory()->withTeam()->create();
    $foreign = Lead::factory()->for($outsider->currentTeam)->create();

    expect(fn () => resolve(CreateNoteForRecord::class)->execute($this->user, $foreign, ['title' => 'Nope']))
        ->toThrow(HttpException::class);

    expect($foreign->notes()->count())->toBe(0);
});

it('rolls back the note when the link cannot be written', function (): void {
    $lead = Lead::factory()->recycle([$this->user, $this->team])->create();

    // A title longer than the column allows aborts inside the transaction, so
    // neither the note nor its link should survive.
    try {
        resolve(CreateNoteForRecord::class)->execute($this->user, $lead, [
            'title' => str_repeat('a', 400),
        ]);
    } catch (Throwable) {
        // expected
    }

    expect($lead->notes()->count())->toBe(0);
});
