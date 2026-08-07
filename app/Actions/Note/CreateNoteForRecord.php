<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\Enums\CreationSource;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a note and links it to a pipeline record in one step.
 *
 * Exists so the kanban card panel can add a note without leaving the board.
 * CreateNote only accepts company/people/deal targets, and Note has no inverse
 * relation for leads or orders, so the link is made from the record's own
 * polymorphic side.
 */
final readonly class CreateNoteForRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, Lead|Deal|Order $record, array $data): Note
    {
        abort_unless($user->can('update', $record), 403);
        abort_unless($user->can('create', Note::class), 403);

        return DB::transaction(function () use ($user, $record, $data): Note {
            $note = resolve(CreateNote::class)->execute($user, $data, CreationSource::WEB);

            $record->notes()->attach($note->getKey());

            return $note;
        });
    }
}
