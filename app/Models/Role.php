<?php namespace App\Models;

use Shanmuga\LaravelEntrust\Models\EntrustRole;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LaravelEntrustRoleTrait;
class Role extends Model
{

    use LaravelEntrustRoleTrait;
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
        $this->table = Config::get('entrust.tables.roles');
    }

    public function user()
    {
        return $this->belongsToMany('App\Models\User','role_user','role_id','user_id'); ;
    }
    public function karyawan()
    {
        return $this->belongsToMany('App\Models\Karyawan','role_user','role_id','user_id');
    }
}
