<?php

namespace EMedia\QuickData\Entities\Repositories;

use EMedia\Helpers\Exceptions\Auth\InsufficientPermissionsException;
use EMedia\Helpers\Exceptions\Auth\UserNotFoundException;
use EMedia\QuickData\Entities\Search\SearchFilter;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements DataRepositoryInterface
{

	protected $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function newModel()
	{
		$class = get_class($this->model);
		return new $class;
	}

	public function all($relationships = [])
	{
		$query = $this->model->select();
		foreach ($relationships as $relation)
		{
			$query->with($relation);
		}
		return $query->get();
	}

	public function paginate($perPage, $relationships = [], $filters = [], $orFilters = [])
	{
		$query = $this->model->select();
		foreach ($relationships as $relation)
		{
			$query->with($relation);
		}
		if ( ! empty($filters))
		{
			foreach ($filters as $filterField => $filterValue)
			{
				$query->where($filterField, 'LIKE', '%' . $filterValue . '%');
			}
		}
		if (is_countable($orFilters) && count($orFilters) > 0)
		{
			$query->where(function ($q) use ($orFilters)
			{
				foreach ($orFilters as $filterField => $filterValue)
				{
					$q->orWhere($filterField, 'LIKE', '%' . $filterValue . '%');
				}
			});
		}
		$query->orderBy('id', 'desc');
		return $query->paginate($perPage);
	}

	public function create($input)
	{
		$model = new $this->model();
		$model->fill($input);
		$model->save();
		return $model;
	}

	public function findOrCreate($id, $input)
	{
		// update or create new record
		if (empty($id))
		{
			$model = $this->create($input);
		}
		else
		{
			$model = $this->find($id);
			$this->update($model, $input);
		}
		return $model;
	}

	public function find($id, $relationships = [])
	{
		$query = $this->model->select();
		foreach ($relationships as $relation)
		{
			$query->with($relation);
		}
		return $query->find($id);
	}

	public function findOrFail($id, $relationships = [])
	{
		$query = $this->model->select();
		foreach ($relationships as $relation)
		{
			$query->with($relation);
		}
		return $query->findOrFail($id);
	}

	public function findByUuid($uuid)
	{
		return $this->model->where('uuid', $uuid)->first();
	}

	public function findBySlug($slug, $slugFieldName = 'slug')
	{
		return $this->model->where($slugFieldName, $slug)->first();
	}

	public function update($model, $updateData)
	{
		$model->fill($updateData);
		$model->save();
		return $model;
	}

	public function save($model)
	{
		return $model->save();
	}

	public function delete($id)
	{
		$model = $this->model->find($id);
		if ($model) {
			$model->delete();
		}
		return true;
	}

	public function allAsList()
	{
		$allItems = $this->all();
		return $this->convertToList($allItems);
	}

	public function convertToList($collection)
	{
		$itemsData = [];
		foreach ($collection as $item)
		{
			$itemsData[] = ['value' => $item->id, 'name' => $item->name];
		}
		return $itemsData;
	}

	/**
	 *
	 * Perform a search for the models
	 * Eg:
	 * $projectRepository->search();
	 *
	 * Optionally pass a filter
	 * $filter = new SearchFilter();
	 * $filter->where('client_id', $projectId);
	 * $projectRepository->search([], $filter);
	 *
	 * You need to use the `EMedia\QuickData\Entities\Search\SearchableTrait` trait on the model.
	 *
	 * @param array             $relationships
	 * @param SearchFilter|null $filter
	 *
	 * @return mixed
	 */
	public function search(array $relationships = [], SearchFilter $filter = null)
	{
		if (!$filter) $filter = new SearchFilter();
		if (is_countable($relationships) && count($relationships)) $filter->with($relationships);

		$query = $this->model->query();

		$this->addSearchQuery($query, $filter->getSearchQuery());

		if (method_exists($this, 'addSearchQueryFilters')) {
			$this->addSearchQueryFilters($query);
		}

		$this->addOrderBy($query, $filter->getOrderBy());
		$this->addRelationships($query, $filter->getWith());
		$this->addRelationshipCounts($query, $filter->getWithCounts());

		// build the where clause for the filter
		$where = $filter->getWhere();
        if (count($where)) {
            foreach ($where as $clause) {
                if (count($clause)) {
                    switch (strtoupper($clause['operator'])) {
						case 'IN':
							$query->whereIn($clause['key'], $clause['value']);
							break;
						case 'NOT IN':
							$query->whereNotIn($clause['key'], $clause['value']);
							break;
						default:
							$query->where($clause['key'], $clause['operator'], $clause['value']);
							break;
					}
                }
            }
        }

        if ($filter->shouldPaginate()) {
			return $query->paginate($filter->perPage());
		} else {
			return $query->get();
		}
	}

	protected function getPerPage($perPage = null)
	{
		if (!$perPage)
			$perPage = ($perPage)?: config('settings.admin.perPage', 20);

		return $perPage;
	}

	protected function addOrderBy(&$query, $orderBy = [])
	{
		if (count($orderBy)) {
			foreach ($orderBy as $orderCol => $orderDirection) {
				$query->orderBy($orderCol, $orderDirection);
			}
		} else {
			$this->addDefaultOrderBy($query);
		}
	}

	protected function addDefaultOrderBy(&$query)
	{
		$query->orderBy('id', 'desc');
	}

	protected function addRelationships(&$query, $relationships = [])
	{
		if (count($relationships)) {
			$query->with($relationships);
		}
	}

	protected function addRelationshipCounts(&$query, $relationships = [])
	{
		if (count($relationships)) {
			$query->withCount($relationships);
		}
	}

	protected function addSearchQuery(&$query, $searchQuery = null)
	{
		$searchQuery = ($searchQuery)?: request()->get('q');
		if ($searchQuery) $query->search($searchQuery);
	}

	/**
	 *
	 * Authorize and modify a query based on the user's permissions.
	 * Eg.
	 * $queryConditions = [
		  'view-employees-global' => null,
		  'view-employees-regional' => function (&$query, $user) {
		  	$region = $this->getEmployeeRegionByUserId($user->id);
			$query->whereHas('office_location', function ($q) use ($region) {
			$q->where('region', $region);
		  });
		 }
	   ];
	 * $this->authorizeQuery($query, $queryConditions, true);
	 *
	 * @param       $query
	 * @param array $queryConditions
	 * @param bool  $rejectIfNoMatch	Throw an exception if no permissions are matched
	 * @param bool  $breakOnFirstMatch	Skip processing after the first matched permission.
	 *
	 * @throws InsufficientPermissionsException
	 * @throws UserNotFoundException
	 */
	public function authorizeQuery(&$query, $queryConditions = [], $rejectIfNoMatch = false, $breakOnFirstMatch = true)
	{
		$user = auth()->user();
		if (!$user) throw new UserNotFoundException("User must be logged in to perform this operation.");

		$calledPermissionCount = 0;
		if (count($queryConditions)) {
			foreach ($queryConditions as $queryPermission => $callableFunction) {
				if ($user->can($queryPermission)) {
					if ($breakOnFirstMatch && $calledPermissionCount > 0) break;

					if ($callableFunction) $callableFunction($query, $user);

					$calledPermissionCount++;
				}
			}
		}

		if ($rejectIfNoMatch && $calledPermissionCount === 0)
			throw new InsufficientPermissionsException();
	}

	/**
	 *
	 * Fill model data from a request
	 *
	 * @param Request $request
	 * @param null    $id
	 *
	 * @return Model
	 */
	public function fillFromRequest(\Illuminate\Http\Request $request, $id = null)
	{
		if ($id === null) {
			$entity = $this->model;
		} else {
			$entity = $this->model->find($id);
		}

		if (!$entity) {
			throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
		}

		$data = $request->all();
		$entity->fill($data);

		if (method_exists($this, 'fillCustomFields')) {
			$this->fillCustomFields($request, $entity);
		}

		$entity->save();

		if (method_exists($this, 'fillCustomFieldsPostSave')) {
			$this->fillCustomFieldsPostSave($request, $entity);
		}

		return $entity;
	}

}
