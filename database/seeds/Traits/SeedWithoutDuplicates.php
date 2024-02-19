<?php
namespace EMedia\QuickData\Database\Seeds\Traits;

use Cocur\Slugify\Slugify;

trait SeedsWithoutDuplicates
{

	private function seedButDontCreateDuplicates(array $entityDataList,
		$entityClassName,
		$nameField = 'name',
		$whereField = 'slug')
	{
		foreach ($entityDataList as $entityData) {

			if (is_array($entityData)) {
				$entityName = $entityData[$nameField];
			} else {
				$entityName = $entityData;
			}

			if ($whereField !== $nameField) {
				$whereValue = (new Slugify())->slugify($entityName);
			} else {
				$whereValue = $entityData[$nameField];
			}

			$entityModel = app($entityClassName);
			$existingEntity = $entityModel->where($whereField, $whereValue)->first();

			if (!$existingEntity) {
				if (!is_array($entityData)) {
					$entityData = [$nameField => $entityData];
				}
				$entityModel->create($entityData);
			}
		}
	}

}