<?php

namespace App\Repositories\Eloquent;

use App\Models\Floor;
use Illuminate\Database\Eloquent\Collection;

class FloorRepository
{
    public function getByPropertyId(int $propertyId): Collection
    {
        return Floor::where('property_id', $propertyId)
            ->with('rooms.services')
            ->orderBy('sort_order')
            ->get();
    }

    public function findById(int $id): ?Floor
    {
        return Floor::with(['rooms.services', 'property'])->find($id);
    }

    public function create(array $data): Floor
    {
        return Floor::create($data);
    }

    public function update(Floor $floor, array $data): bool
    {
        return $floor->update($data);
    }

    public function delete(Floor $floor): bool
    {
        return $floor->delete();
    }

    public function getMaxSortOrder(int $propertyId): int
    {
        return Floor::where('property_id', $propertyId)->max('sort_order') ?? 0;
    }
}
