<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\Pipeline\LeadStage;
use App\Enums\Pipeline\LeadSubStage;
use App\Models\Concerns\BelongsToTeamCreator;
use App\Models\Concerns\HasCreator;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTeam;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property Carbon|null $deleted_at
 * @property CreationSource $creation_source
 * @property LeadStage $stage
 * @property LeadSubStage|null $sub_stage
 */
#[ObservedBy(LeadObserver::class)]
#[Fillable([
    'creation_source',
    'stage',
    'sub_stage',
    'order_column',
])]
final class Lead extends Model implements HasCustomFields, HasTimeline
{
    use BelongsToTeamCreator;
    use HasCreator;

    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    use HasNotes;
    use HasTeam;
    use HasUlids;
    use InteractsWithTimeline;
    use LogsActivity;
    use SoftDeletes;
    use SortableTrait;
    use UsesCustomFields;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'creation_source' => CreationSource::WEB,
        'stage' => LeadStage::NEW,
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'creation_source' => CreationSource::class,
            'stage' => LeadStage::class,
            'sub_stage' => LeadSubStage::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }

    /**
     * The deal this lead was converted into, if it has been won.
     *
     * @return HasOne<Deal, $this>
     */
    public function deal(): HasOne
    {
        return $this->hasOne(Deal::class);
    }

    /**
     * @return MorphToMany<Task, $this>
     */
    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, 'taskable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept([
                'id', 'team_id', 'creator_id', 'creation_source', 'custom_fields',
                'created_at', 'updated_at', 'deleted_at', 'order_column',
            ])
            ->useLogName('crm')
            ->setDescriptionForEvent(fn (string $eventName): string => $eventName);
    }

    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog(mergedRenderer: 'merged-activity');
    }
}
