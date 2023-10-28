<?php

namespace App\Http\Resources\Event;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    =>  $this->user->id,            
            'first_name'            =>  $this->user->first_name,            
            'last_name'             =>  $this->user->last_name,            
            'profile_image'         =>  $this->user->profile_image       
        ];
    }
}
