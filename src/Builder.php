<?php namespace Monger\EloquentEAV;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Monger\EloquentEAV\Models\StringAttribute;
use Monger\EloquentEAV\Relations\StringAttribute as StringAttributeRelation;

class Builder extends EloquentBuilder {

	/**
	 * Eager load the relationships for the models.
	 *
	 * @param  array	$models
	 *
	 * @return array
	 */
	public function eagerLoadRelations(array $models)
	{
		$models = parent::eagerLoadRelations($models);

		//load the various attributes
		$this->loadPrimitiveAttributes($models);

		return $models;
	}

	/**
	 * Loads attributes the provided models if the parent model should load each of them
	 *
	 * @param array		$models
	 *
	 * @return array
	 */
	protected function loadPrimitiveAttributes(array $models)
	{
		//load the string attributes
		if ($this->model->hasStringAttributes())
		{
			$this->loadStringAttributes($models);
		}

		//load the integer attributes
		if ($this->model->hasIntegerAttributes())
		{
			$this->loadIntegerAttributes($models);
		}

		//load the float attributes
		if ($this->model->hasFloatAttributes())
		{
			$this->loadFloatAttributes($models);
		}

		return $models;
	}

	/**
	 * Eager load the string attributes
	 *
	 * @param  array  $models
	 *
	 * @return array
	 */
	protected function loadStringAttributes(array $models)
	{
		// First we will "back up" the existing where conditions on the query so we can
		// add our eager constraints. Then we will merge the wheres that were on the
		// query back to it in order that any where conditions might be specified.
		$relation = $this->getStringAttributeRelation();

		$relation->addEagerConstraints($models);

		$models = $relation->initRelation($models, 'stringAttributes');

		// Once we have the results, we just match those back up to their parent models
		// using the relationship instance. Then we just return the finished arrays
		// of models which have been eagerly hydrated and are readied for return.
		$results = $relation->get($relation->getAttributeFields());

		return $relation->match($models, $results, 'stringAttributes');
	}

	/**
	 * Gets the string attribute relation
	 */
	protected function getStringAttributeRelation()
	{
		return Relation::noConstraints(function()
		{
			$instance = new StringAttribute;

			$table = $instance->getTable();

			return new StringAttributeRelation($instance->newQuery(), $this->model);
		});
	}

}