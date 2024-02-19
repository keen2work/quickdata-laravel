<?php

namespace EMedia\QuickData\Entities\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltersByLatLngTrait
{


	/**
	 *
	 * Filter a query by latitude, longitude and distance
	 * Use this method on a repository to extend an existing query
	 * Example:
	 *
	 * 	protected function addSearchQueryFilters(Builder $query): void
	{
	// distance
	$this->filterByLatLng(request(), $query);
	}
	 *
	 * @param Request $request
	 * @param Builder $query
	 * @param int     $defaultDistance
	 * @param string  $unit
	 */
	protected function filterByLatLng(Request $request, Builder $query, $defaultDistance = 10000, $unit = 'km')
	{
		if ($unit === 'km') {
			$circleRadius = 6371; // kilometers
		} else {
			$circleRadius = 3959; // miles
		}

		if ($request->filled('latitude') && $request->filled('longitude')) {
			$latitude = $request->latitude;
			$longitude = $request->longitude;

			$distance = $defaultDistance;
			if ($request->filled('distance')) {
				$distance = $request->distance;
			}

			$query->selectRaw('*, (? * acos(cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude)))
                    ) AS distance', [$circleRadius, $latitude, $longitude, $latitude]);

			$query->whereRaw('(? * acos(cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude)))
                    ) < ?', [$circleRadius, $latitude, $longitude, $latitude, $distance]);

			if ($request->filled('sort') && $request->sort === 'distance') {
				$query->orderBy('distance');
			}
		}
	}

}