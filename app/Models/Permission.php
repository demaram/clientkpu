<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LaravelEntrustPermissionTrait;
use Illuminate\Support\Facades\Config;

class Permission extends Model
{
     use LaravelEntrustPermissionTrait;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('entrust.tables.permissions');
    }
}
