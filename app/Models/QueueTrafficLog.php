<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class QueueTrafficLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'total_download_mbps',
        'total_upload_mbps',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }
}
