<?php namespace Monger\EloquentEAV;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Monger\EloquentEAV\Relations\StringAttribute;
use Monger\EloquentEAV\Relations\HasMany;
use Monger\EloquentEAV\Relations\HasOne;
use Monger\EloquentEAV\Relations\BelongsTo;

abstract class Model extends BaseModel {

	/**
	 * Determines if this model has string attributes
	 *
	 * @var bool
	 */
	protected $stringAttributes = false;

	/**
	 * Determines if this model has integer attributes
	 *
	 * @var bool
	 */
	protected $integerAttributes = false;

	/**
	 * Determines if this model has float attributes
	 *
	 * @var bool
	 */
	protected $floatAttributes = false;

	/**
	 * The primitive attribute types
	 *
	 * @var array
	 */
	protected $primitiveAttributeTypes = array('string', 'float', 'integer');

	/**
	 * The entity string name (if different from the table name)
	 *
	 * @var string
	 */
	protected $entityType;

	/**
	 * Gets the stringAttributes property
	 *
	 * @return bool
	 */
	public function hasStringAttributes()
	{
		return $this->stringAttributes;
	}

	/**
	 * Gets the integerAttributes property
	 *
	 * @return bool
	 */
	public function hasIntegerAttributes()
	{
		return $this->integerAttributes;
	}

	/**
	 * Gets the floatAttributes property
	 *
	 * @return bool
	 */
	public function hasFloatAttributes()
	{
		return $this->floatAttributes;
	}

	/**
	 * Gets the model's entity type, which is the table name if the $entityType property isn't set
	 *
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->entityType ?: $this->getTable();
	}

	/**
	 * Define a many-to-many relationship.
	 *
	 * @param string	$related
	 * @param array		$attributeIds
	 *
	 * @return \Monger\EloquentEAV\Relations\HasMany
	 */
	public function hasManyEav($related, $attributeIds = null)
	{
		$instance = new $related;

		$query = $instance->newQuery();

		$attributeIds = $this->getInstanceAttributeIds($instance, $attributeIds);

		return new HasMany($query, $this, 'monger.ObjectAttributes', 'objectID', 'value', 'objectType', 'attributeID', $attributeIds);
	}

	/**
	 * Define a one-to-one owner EAV relationship
	 *
	 * @param string	$instance
	 *
	 * @return \Monger\EloquentEAV\Relations\HasOne
	 */
	public function hasOneEav($related)
	{
		$instance = new $related;

		$query = $instance->newQuery();

		$attributeIds = $this->getInstanceAttributeIds($instance, null);

		return new HasOne($query, $this, 'monger.ObjectAttributes', 'objectID', 'value', 'objectType', 'attributeID', $attributeIds);
	}

	/**
	 * Define a one-to-one owned EAV relationship
	 *
	 * @param string	$instance
	 *
	 * @return \Monger\EloquentEAV\Relations\BelongsTo
	 */
	public function belongsToEav($related)
	{
		$instance = new $related;

		$query = $instance->newQuery();

		$attributeIds = $this->getInstanceAttributeIds($this, null);

		//the BelongsTo object relation is effectively the same as a HasOne with the fields reversed
		return new BelongsTo($query, $this, 'monger.ObjectAttributes', 'objectID', 'value', 'objectType', 'attributeID', $attributeIds);
	}

	/**
	 * Define a one-to-many EAV relationship
	 *
	 * @param \Monger\EloquentEAV\Model	$instance
	 * @param mixed								$attributeIds
	 *
	 * @return array
	 */
	protected function getInstanceAttributeIds(Model $instance, $attributeIds)
	{
		if (!$attributeIds)
		{
			if (!method_exists($instance, 'getAttributeId'))
				throw new \Exception("In order to set up an EAV relationship, there needs to be a getAttributeId() method on the class: " .
										get_class($instance));

			$ids = $instance->getAttributeId();

			return is_array($ids) ? $ids : [$ids];
		}

		return $attributeIds;
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		//if the key matches something in the parent, return it
		$value = parent::getAttribute($key);

		if ($value !== null)
			return $value;

		//otherwise we should try to find it in the various attributes relations
		return $this->getPrimitiveAttributeValue($key);
	}

	/**
	 * Get a new query builder for the model's table.
	 *
	 * @param  bool  $excludeDeleted
	 *
	 * @return \Monger\EloquentEAV\Builder|static
	 */
	public function newQuery($excludeDeleted = true)
	{
		$builder = new Builder($this->newBaseQueryBuilder());

		// Once we have the query builders, we will set the model instances so the
		// builder can easily access any information it may need from the model
		// while it is constructing and executing various queries against it.
		$builder->setModel($this)->with($this->with);

		if ($excludeDeleted and $this->softDelete)
		{
			$builder->whereNull($this->getQualifiedDeletedAtColumn());
		}

		return $builder;
	}

	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$attributes = parent::toArray();
		$primitiveAttributes = $this->primitiveAttributesToArray();

		//remove the primitive attributes from the existing array
		unset($attributes['string_attributes'], $attributes['float_attributes'], $attributes['integer_attributes']);

		return array_merge($primitiveAttributes, $attributes);
	}

	/**
	 * Convert the various primitive attributes to an array
	 *
	 * @return array
	 */
	protected function primitiveAttributesToArray()
	{
		$attributes = array();

		foreach ($this->primitiveAttributeTypes as $type)
		{
			$attributes += $this->convertPrimitiveTypeToArray($type);
		}

		return $attributes;
	}

	/**
	 * Convert the string attributes to an array
	 *
	 * @param string	$type
	 *
	 * @return array
	 */
	protected function convertPrimitiveTypeToArray($type)
	{
		$output = array();

		if ($attributes = array_get($this->relations, $type . 'Attributes'))
		{
			foreach ($attributes as $attribute)
			{
				$output[$attribute['name']] = $attribute['value'];
			}
		}

		return $output;
	}

	/**
	 * Gets a primitive attribute value by key
	 *
	 * @param string	$key
	 *
	 * @return mixed
	 */
	protected function getPrimitiveAttributeValue($key)
	{
		//iterate over the primitive attribute types to see if we can find the key in any of those arrays
		foreach ($this->primitiveAttributeTypes as $type)
		{
			$value = $this->getPrimitiveAttributeValueByType($key, $type);

			if ($value !== null)
				return $value;
		}
	}

	/**
	 * Gets a primitive attribute value by key and primitive type
	 *
	 * @param string	$key
	 * @param string	$type
	 *
	 * @return mixed	string | null
	 */
	protected function getPrimitiveAttributeValueByType($key, $type)
	{
		if ($attributes = array_get($this->relations, $type . 'Attributes'))
		{
			foreach ($attributes as $attribute)
			{
				if ($attribute['name'] === $key)
					return $attribute['value'];
			}
		}
	}
}