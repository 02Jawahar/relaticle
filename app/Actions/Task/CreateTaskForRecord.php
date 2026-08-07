<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Enums\CreationSource;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a task and links it to a pipeline record in one step.
 *
 * Counterpart to CreateNoteForRecord: the kanban card panel adds tasks without
 * leaving the board, and the link is made from the record's polymorphic side
 * because Task has no inverse relation for leads or orders.
 */
final readonly class CreateTaskForRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Lead|Deal|Order $record, array $data): Task
    {
        abort_unless($user->can('update', $record), 403);
        abort_unless($user->can('create', Task::class), 403);

        return DB::transaction(function () use ($user, $record, $data): Task {
            $task = resolve(CreateTask::class)->execute($user, $data, CreationSource::WEB);

            $record->tasks()->attach($task->getKey());

            return $task;
        });
    }
}
