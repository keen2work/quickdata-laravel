<?php

namespace EMedia\QuickData\Http\Controllers;

use EMedia\QuickData\Entities\Repositories\DataRepositoryInterface;
use Illuminate\Support\Facades\Validator;

trait HasJsonCRUD
{

	protected $model;
	protected $dataRepo;
	protected $moduleName;

	/**
	 * Display a listing of the resource.
	 * GET /entities
	 *
	 * @return Response
	 */
	public function index()
	{
		return $this->dataRepo->all();
	}


	/**
	 * Filter query results based on the user's request for index() method
	 *
	 * @param DataRepositoryInterface $dataRepo
	 * @param array $relationships
	 * @return mixed
	 */
	protected function filterResults(DataRepositoryInterface $dataRepo, $relationships = [])
	{
		// Example parameters from filter requests
		// count:10
		// filter[full_name]:roger
		// filter[location]:london
		// page:1
		// sorting[name]:asc

		$filters 	= input()->get('filter');
		$orFilters 	= null;

		if (is_array($filters) && count($filters) > 0) {
			// restrict the filtering columns in the database
			$filters = \Illuminate\Support\Arr::only($filters, $this->model->searchable());
			// set $orFilter if multiple columns needs to be filtered with 'OR'
		}

		// results per page
		$perPageCount = input()->get('count');
		if (empty($perPageCount)) $perPageCount = 20;

		return $dataRepo->paginate(
			$perPageCount,
			$relationships,
			$filters,
			$orFilters
		);
	}

	/**
	 * Append default values to the schema data
	 *
	 * @param $dataSchema
	 * @return array
	 */
	protected function appendDefaultValues($dataSchema)
	{
		$searchableFields = $this->model->searchable();
		$schemaWithDefaults = [];
		foreach ($dataSchema as $fieldData)
		{
			// set the default names
			if (empty($fieldData['name'])) $fieldData['name'] = \Illuminate\Support\Str::studly(reverse_snake_case($fieldData['field']));

			// check the model and mark 'searchable' fields
			if (in_array($fieldData['field'], $searchableFields)) {
				$fieldData['searchable'] = true;
			}
			$schemaWithDefaults[] = $fieldData;
		}
		return $schemaWithDefaults;
	}

	/**
	 * Store a newly created resource in storage.
	 * POST /entities
	 *
	 * @return Response
	 */
	protected function store()
	{
		// validations based on model rules
		$validator = Validator::make(input()->all(), $this->model->getRules());
		if ($validator->fails())
		{
			$response = [
				'result' 	=> false,
				'message' 	=> implode(' ', $validator->messages()->all())
			];
			return response()->json($response, 422);
		}

		return $this->storeOrUpdate();
	}

	/**
	 * Update the specified resource in storage.
	 * PUT /entities/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		// validations based on model rules
		$validator = Validator::make(input()->all(), $this->model->getRules());
		if ($validator->fails())
		{
			$response = [
				'result' 	=> false,
				'message' 	=> implode(' ', $validator->messages()->all())
			];
			return response()->json($response, 422);
		}

		return $this->storeOrUpdate($id);
	}


	public function storeOrUpdate($id = null)
	{
		$input = input()->all();

		$model = $this->dataRepo->findOrCreate($id, $input);

		$model = $this->checkAndUpdateRelationships($model, $input, true);

		return $model;
	}

	protected function checkAndUpdateRelationships($model, $input, $recursive = false)
	{
		// check and update hasMany relationships, if any
		if (method_exists($model, 'getManyToManyRelations'))
		{
			foreach ($model->getManyToManyRelations() as $relation)
			{
				$relationshipsData = array_pull($input, $relation);
				// TODO: add the relationship
				$model = $this->syncRelationships($model, $relationshipsData, $relation);
			}
		}

		if (method_exists($model, 'getHasManyRelations'))
		{
			foreach ($model->getHasManyRelations() as $relation)
			{
				$relationshipsData = array_pull($input, $relation);
				$model = $this->saveRelationships($model, $relationshipsData, $relation, true, $recursive);
			}
		}
		return $model;
	}

	protected function saveRelationships($model, $relationshipsData, $relation, $sync = true, $recursive = false)
	{
		// check if relationship actually exists
		// eg: $task->checklist_items()  // hasMany relation
		if (method_exists($model, $relation))
		{
			// get the relationship's class
			// eg: CheckListItem = $task->checklist_items()->getRelated();
			$relatedClass = get_class($model->$relation()->getRelated());

			if ($sync)
			{
				// find all existing related items for this class
				// because we need to delete any missing relations at the end
				$current = $relatedClass::where($model->$relation()->getForeignKey(), $model->id)->lists('id')->toArray();
				$newIds  = [];
			}

			// create the new objects or use Ids
			$relatedModels = [];
			if ( ! empty($relationshipsData))
			{
				foreach ($relationshipsData as $relationshipData)
				{
					if (empty($relationshipData['id']))
					{
						// if there's no entity, create a new Model
						$relatedModel = new $relatedClass();
					}
					else
					{
						// fetch the related object
						$relatedModel = $relatedClass::find($relationshipData['id']);
					}

					// fill and save model
					if ( ! empty($relatedModel))
					{
						if ( ! empty($relationshipData))
						{
							$relatedModel->fill($relationshipData);
							$relatedModel->save();

							// look for nested relationships
							if ($recursive)
							{
								$relatedModel = $this->checkAndUpdateRelationships($relatedModel, $relationshipData, $recursive);
							}

							$relatedModels[] = $relatedModel;
							$newIds[] = $relatedModel->id;
						}
					}
				}
			}

			if ($sync)
			{
				// delete the missing Ids from the updated list
				$detachIds	 = array_diff($current, $newIds);
				$relatedClass::destroy($detachIds);
			}

			$model->$relation()->saveMany($relatedModels);
		}
		return $model;
	}


	protected function syncRelationships($model, $relationshipsData, $relation)
	{
		// check if the relationship actually exists
		// eg: $order->products()
		if (method_exists($model, $relation))
		{
			// get the relationship's class
			// eg: $order->products()->getRelated();
			$relatedClass = get_class($model->$relation()->getRelated());

			// create the new objects or use Ids
			$relatedModels = [];
			if ( ! empty($relationshipsData))
			{
				// TODO: handle 1 to many relationships, currently all are treated as many to many
				// if this is not an array, convert it to an array
//				if ( ! is_array($relationshipsData))
//				{
//					$relationshipsData = [];
//					$relationshipsData['id'] = $relationshipsData;
//				}

				foreach ($relationshipsData as $relationshipData)
				{
					if (empty($relationshipData['id']))
					{
						// if there's no entity, create a new Model
						$relatedModel = new $relatedClass();
						$relatedModel->fill($relationshipData);
						$relatedModel->save();

						// associate the model and build the relationship
						$model->$relation()->save($relatedModel);
					}
					else
					{
						// loop through all the fields in the relationship
						$fillableData = [];
						foreach ($relationshipData as $columnName => $columnValue)
						{
							// only use the fillable pivot values
							// these are set in the Model, using the dot notation
							if ($fillablePivots = $model->getFillablePivots())
							{
								if (in_array($relation . '.' . $columnName, $fillablePivots))
								{
									$fillableData[$columnName] = $columnValue;
								}
							}
						}
						$relatedModels[$relationshipData['id']] = $fillableData;
					}
				}
			}

			// save relationships
			// TODO: sync or save - only sync for m-2-m relations
			$model->$relation()->sync($relatedModels);
		}
		return $model;
	}

	/**
	 * Display the specified resource.
	 * GET /model-name/{id}
	 *
	 * @param  int  $id
	 * @return \Model
	 */
	public function show($id)
	{
		return $this->dataRepo->find($id);
	}


	/**
	 * Remove the specified resource from storage.
	 * DELETE /entities/{id}
	 *
	 * @param  int  $id
	 * @return array
	 */
	public function destroy($id)
	{
		return ['result' => $this->dataRepo->delete($id)];
	}


	/**
	 * Flatten the pivot values on a model
	 *
	 * @param $model
	 * @return array
	 */
	protected function flatternModelPivots($model)
	{
		$newModelData = [];

		foreach ($model as $key => $value)
		{
			// find the 'pivot' table and flattern the values
			if ($key === 'pivot')
			{
				$newValues = [];
				foreach ($model[$key] as $pivotKey => $pivotValue)
				{
					// escape the foreign keys
					if (strpos($pivotKey, '_id') === false)
					{
						$newValues[$pivotKey] = $pivotValue;
					}
				}
				$newModelData = array_merge($newModelData, $newValues);
			}
			elseif (is_array($value))
			{
				$flatternArray = $this->flatternModelPivots($value);
				$newModelData[$key] = $flatternArray;
			}
			else
			{
				$newModelData[$key] = $value;
			}
		}

		return $newModelData;
	}


}
