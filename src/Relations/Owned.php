<?php namespace Monger\EloquentEAV\Relations;

abstract class Owned extends ObjectRelation {

	/**
	 * Get the "local name field" in a relationship. For an owner relationship, this will be the entityNameField, and for the owned it will
	 * be attributeNameField.
	 *
	 * @return string
	 */
	public function getLocalNameField()
	{
		return $this->attributeNameField;
	}

	/**
	 * Get the "other name field" in a relationship. For an owner relationship, this will be the attributeNameField, and for the owned it will
	 * be entityNameField.
	 *
	 * @return string
	 */
	public function getOtherNameField()
	{
		return $this->entityNameField;
	}

	/**
	 * Get the "local key" in a relationship. For an owner relationship, this will be the entityKey, and for the owned it will be attributeKey.
	 *
	 * @return string
	 */
	public function getLocalKey()
	{
		return $this->attributeKey;
	}

	/**
	 * Get the "other key" in a relationship. For an owner relationship, this will be the attributeKey, and for the owned it will be entityKey.
	 *
	 * @return string
	 */
	public function getOtherKey()
	{
		return $this->entityKey;
	}

	/**
	 * Get the local type identifier in a relationship. For an owner relationship, this will be the entity name field, and for the owned it will
	 * be attribute id.
	 *
	 * @return array
	 */
	public function getLocalTypeId()
	{
		return $this->attributeIds;
	}

	/**
	 * Get the local type identifier in a relationship. For an owner relationship, this will be the attribute id, and for the owned it will
	 * be entity name field.
	 *
	 * @return array
	 */
	public function getOtherTypeId()
	{
		return [$this->related->getEntityType()];
	}

}