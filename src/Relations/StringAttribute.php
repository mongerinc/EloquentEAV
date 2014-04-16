<?php namespace Monger\EloquentEAV\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StringAttribute extends PrimitiveAttribute {

	/**
	 * Returns the name of connector table for this particular attribute
	 *
	 * @return string
	 */
	protected function getAttributeConnectorTable()
	{
		return 'monger.StringAttributes';
	}

}