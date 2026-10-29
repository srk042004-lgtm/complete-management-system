<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DemoRequest extends Model
{
    protected $fillable = [
        'business_name', 'business_type', 'contact_name', 'phone', 'email', 'city', 'already_using', 'address', 'status',
    ];

    public function trialAccount(): HasOne
    {
        return $this->hasOne(TrialAccount::class);
    }
}
