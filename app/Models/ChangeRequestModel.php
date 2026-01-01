<?php   
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestModel extends Model
{   
    protected $table = 'change_requests';

    protected $fillable = [
        'group',
        'group_id',
        'status',
        'tipe',
        'created_by',
        'updated_by'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the approvals for the change request.
     */
    public function approvals()
    {
        return $this->hasMany(ChangeRequestApproval::class, 'change_request_id');
    }

    public function karyawanCr()
    {
        return $this->belongsTo(Karyawan::class, 'group_id');
    }

    /**
     * Get the approval views for the change request.
     */
    public function approvalViews()
    {
        return $this->hasMany(ChangeRequestApprovalView::class, 'change_request_id');
    }

    /**
     * Get the details for the change request.
     */
    public function details()
    {
        return $this->hasMany(ChangeRequestDetail::class, 'change_request_id');
    }

    /**
     * Get the user who created the change request.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the change request.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the logs for the change request.
     */
    public function logs()
    {
        return $this->hasMany(ChangeRequestLog::class, 'change_request_id');
    }
}
