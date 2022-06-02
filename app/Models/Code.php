<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Code
 *
 * @property string $hash
 * @property string $code
 * @property int $expires_at
 * @method static Builder|Code newModelQuery()
 * @method static Builder|Code newQuery()
 * @method static Builder|Code query()
 * @method static Builder|Code whereCode($value)
 * @method static Builder|Code whereExpiresAt($value)
 * @method static Builder|Code whereHash($value)
 * @mixin \Eloquent
 */
class Code extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'codes';

    // No PK
    public $primaryKey = null;
    public $incrementing = false;

    // No timestamps
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'hash',
        'code',
        'expires_at',
    ];

    public function isExpired(): bool
    {
        return (Carbon::now()->timestamp > $this->expires_at);
    }
}
