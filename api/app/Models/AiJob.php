<?php

namespace App\Models;

use App\Enums\AiJobStatus;
use App\Enums\AiJobType;
use App\Models\Concerns\BelongsToHousehold;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiJob extends Model
{
    use BelongsToHousehold, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'type',
        'status',
        'input',
        'result',
        'error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AiJobType::class,
            'status' => AiJobStatus::class,
            'input' => 'array',
            'result' => 'array',
        ];
    }

    public function markProcessing(): void
    {
        $this->update(['status' => AiJobStatus::Processing]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markCompleted(array $result): void
    {
        $this->update(['status' => AiJobStatus::Completed, 'result' => $result]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => AiJobStatus::Failed, 'error' => $error]);
    }
}
