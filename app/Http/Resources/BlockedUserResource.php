<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlockedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'blocker' => [
                'id' => $this->blocker->id,
                'name' => $this->blocker->name,
                'email' => $this->blocker->email,
            ],
            'blocked_user' => [
                'id' => $this->blocked->id,
                'name' => $this->blocked->name,
                'email' => $this->blocked->email,
            ],
            'reason' => $this->reason,
            'blocked_at' => $this->blocked_at,
            'unblocked_at' => $this->unblocked_at,
            'status' => $this->unblocked_at ? 'unblocked' : 'blocked',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
