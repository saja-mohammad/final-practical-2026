<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceRequest extends Model {
    protected $fillable = ['customer_id','technician_id','title','description','priority','status','requested_at'];
    protected $casts = ['requested_at' => 'date'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }
}