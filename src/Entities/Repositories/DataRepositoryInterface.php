<?php

namespace EMedia\QuickData\Entities\Repositories;

interface DataRepositoryInterface
{
	public function newModel();

	public function all($relationships = []);

	public function paginate($perPage, $relationships = [], $filters = [], $orFilters = []);

	public function create($input);

	public function findOrCreate($id, $input);

	public function find($id, $relationships = []);

	public function update($model, $updateData);

	public function save($model);

	public function delete($id);
}