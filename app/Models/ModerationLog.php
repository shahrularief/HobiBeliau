<?php
namespace App\Models;
class ModerationLog extends \Illuminate\Database\Eloquent\Model { protected $guarded = ['id']; public function admin() { return $this->belongsTo(User::class, 'admin_id'); } }
