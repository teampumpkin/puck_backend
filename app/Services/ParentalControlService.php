<?php

namespace App\Services;

use App\Models\V4ParentalControl;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ParentalControlService
{
    public function getParentControl($parentId, $childId)
    {
        return V4ParentalControl::where('parent_id', $parentId)
            ->where('child_id', $childId)
            ->first();
    }

    public function createControl($parentId, $childId)
    {
        try {
            return V4ParentalControl::create([
                'parent_id' => $parentId,
                'child_id'  => $childId,
                'enabled'   => true,
            ]);
        } catch (QueryException $e) {
            throw new QueryException('Database query error', $e->getCode(), $e);
        }
    }

    public function toggleControl($parentId, $childId)
    {
        try {
            $parentControl = V4ParentalControl::where('parent_id', $parentId)
                ->where('child_id', $childId)
                ->first();

            if (!$parentControl) {
                $parentControl = V4ParentalControl::create([
                    'parent_id' => $parentId,
                    'child_id'  => $childId,
                    'enabled'   => false,
                ]);
            }

            $parentControl->enabled = !$parentControl->enabled;
            $parentControl->save();

            return $parentControl;
        } catch (QueryException $e) {
            throw new QueryException('Database query error', $e->getCode(), $e);
        }
    }

    public function deleteControl($parentId, $childId)
    {
        try {
            $parentControl = V4ParentalControl::where('parent_id', $parentId)
                ->where('child_id', $childId)
                ->firstOrFail();

            $parentControl->delete();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Parental control or child not found');
        } catch (QueryException $e) {
            throw new QueryException('Database query error', $e->getCode(), $e);
        }
    }
}
