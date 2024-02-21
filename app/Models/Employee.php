<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'employees';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
    }
    public function getDobAttribute($value)
    {
        return date('d-m-Y', strtotime($value));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    // For One-to-One relationship

    public function getDepartment(): HasOne
    {
        return $this->hasOne('App\Models\Department', 'dept_id');
    }
}
