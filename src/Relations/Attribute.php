<?php namespace Monger\EloquentEAV\Relations;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Monger\EloquentEAV\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class Attribute extends HasOneOrMany {

	/**
	 * The attributes table
	 */
	protected $attributesTable = 'demand.Attributes';

	/**
	 * The attributes table
	 */
	protected $attributeIdField = 'attributeID';

	/**
	 * The name of the entity type field on the connector table
	 *
	 * @var string
	 */
	protected $entityTypeField = 'objectType';

	/**
	 * The foreign key type for the relationship.
	 *
	 * @var string
	 */
	protected $entityIdField = 'objectID';

	/**
	 * The entity type of the object
	 *
	 * @var string
	 */
	protected $entityType;

	/**
	 * The entity's Eloquent class
	 *
	 * @var string
	 */
	protected $entityClass;

	/**
	 * Create a new attribute relationship instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder	$query
	 * @param  \Monger\EloquentEAV\Model			$parent
	 *
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent)
	{
		$connectorTable = $this->getAttributeConnectorTable();

		$this->entityTypeField = $connectorTable . '.' . $this->entityTypeField;

		$this->entityIdField = $connectorTable . '.' . $this->entityIdField;

		$this->attributeClass = get_class($parent);

		$this->entityType = $parent->getEntityType();

		parent::__construct($query, $parent, $this->entityIdField, $parent->getKeyName());
	}

	/**
	 * Returns the name of connector table for this particular attribute
	 *
	 * @return string
	 */
	abstract protected function getAttributeConnectorTable();

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		if (static::$constraints)
		{
			parent::addConstraints();

			$this->addAttributeConstraints();
		}
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		parent::addEagerConstraints($models);

		$this->addAttributeConstraints();
	}

	/**
	 * Adds the universal primitive attribute constraints
	 *
	 * @return void
	 */
	protected function addAttributeConstraints()
	{
		$this->query->where($this->entityTypeField, $this->entityType);
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  \Illuminate\Database\Eloquent\Builder  $parent
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getRelationCountQuery(Builder $query, Builder $parent)
	{
		$query = parent::getRelationCountQuery($query, $parent);

		return $query->where($this->entityTypeField, $this->entityType);
	}

	/**
	 * Attach a model instance to the parent model.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function save(BaseModel $model)
	{
		$model->setAttribute($this->getPlainMorphType(), $this->entityClass);

		return parent::save($model);
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param  array  $attributes
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function create(array $attributes)
	{
		$foreign = $this->getForeignAttributesForCreate();

		// When saving a polymorphic relationship, we need to set not only the foreign
		// key, but also the foreign key type, which is typically the class name of
		// the parent model. This makes the polymorphic item unique in the table.
		$attributes = array_merge($attributes, $foreign);

		$instance = $this->related->newInstance($attributes);

		$instance->save();

		return $instance;
	}

	/**
	 * Get the foreign ID and type for creating a related model.
	 *
	 * @return array
	 */
	protected function getForeignAttributesForCreate()
	{
		$foreign = array($this->getPlainForeignKey() => $this->parent->getKey());

		$foreign[last(explode('.', $this->morphType))] = $this->morphClass;

		return $foreign;
	}

	/**
	 * Get the foreign key "type" name.
	 *
	 * @return string
	 */
	public function getMorphType()
	{
		return $this->morphType;
	}

	/**
	 * Get the plain morph type name without the table.
	 *
	 * @return string
	 */
	public function getPlainMorphType()
	{
		return last(explode('.', $this->morphType));
	}

	/**
	 * Get the class name of the parent model.
	 *
	 * @return string
	 */
	public function getMorphClass()
	{
		return $this->morphClass;
	}

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->get();
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return void
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, $this->related->newCollection());
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		return $this->matchMany($models, $results, $relation);
	}

}