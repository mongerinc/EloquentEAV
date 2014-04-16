<?php namespace Monger\EloquentEAV\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class PrimitiveAttribute extends Attribute {

	/**
	 * Adds the universal primitive attribute constraints
	 *
	 * @return void
	 */
	protected function addAttributeConstraints()
	{
		parent::addAttributeConstraints();

		//join the attributes table onto the query
		$connectorOn = $this->query->getModel()->getTable().'.'.$this->attributeIdField;
		$attributesOn = $this->attributesTable.'.'.$this->attributeIdField;

		$this->query->join($this->attributesTable, $connectorOn, '=', $attributesOn);
	}

	/**
	 * Gets the list of attribute fields that are necessary for the primitive attributes
	 *
	 * @return array
	 */
	public function getAttributeFields()
	{
		return ['value', 'name', $this->entityTypeField, $this->entityIdField, $this->attributesTable . '.' . $this->attributeIdField];
	}

}