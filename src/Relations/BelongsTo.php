<?php namespace Monger\EloquentEAV\Relations;

use Illuminate\Database\Eloquent\Collection;

class BelongsTo extends ObjectRelation {

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->first();
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 *
	 * @return void
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, null);
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
		return $this->matchOne($models, $results, $relation);
	}

	/**
	 * Set the join clause for the relation query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder|null
	 *
	 * @return \Monger\EloquentEAV\Relations\ObjectRelation
	 */
	protected function setJoin($query = null)
	{
		$query = $query ?: $this->query;

		// We need to join to the intermediate table on the related model's primary
		// key column with the intermediate table's foreign key for the related
		// model instance. Then we can set the "where" for the parent models.
		$baseTable = $this->related->getTable();

		$key = $baseTable.'.'.$this->related->getKeyName();

		$query->join($this->table, $key, '=', $this->getEntityKey());

		return $this;
	}

	/**
	 * Set the where clause for the relation query.
	 *
	 * @return \Monger\EloquentEAV\Relations\ObjectRelation
	 */
	protected function setWhere()
	{
		$this->query->where($this->getAttributeKey(), '=', $this->parent->getKey());

		return $this->setNameFieldWheres();
	}

	/**
	 * Set the where clause for the relation query.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder		$query
	 *
	 * @return \Monger\EloquentEAV\Relations\ObjectRelation
	 */
	protected function setNameFieldWheres($query = null)
	{
		$query = $query ?: $this->query;

		$query->where($this->getEntityNameField(), '=', $this->related->getEntityType())
				->whereIn($this->getAttributeNameField(), $this->attributeIds);

		return $this;
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 *
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		$this->query->whereIn($this->getAttributeKey(), $this->getKeys($models));

		$this->setNameFieldWheres();
	}

}