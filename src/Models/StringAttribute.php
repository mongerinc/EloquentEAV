<?php namespace Monger\EloquentEAV\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class StringAttribute extends Eloquent {

	protected $connection = 'monger';
	protected $table = 'StringAttributes';
	protected $primaryKey = 'stringAttributeID';

}