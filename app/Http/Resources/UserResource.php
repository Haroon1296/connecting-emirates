<?php

namespace App\Http\Resources;

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
        if($this->user_type == 'user'){
            return $this->user($this);
        } else if($this->user_type == 'hat') {
            return $this->hat($this);
        } else {
            return $this->guest($this);
        }
    }

    private function guest($data)
    {
        return [
            'id'                    =>  $data->id,
            'first_name'            =>  $data->first_name,
            'last_name'             =>  $data->last_name,
            'user_type'             =>  $data->user_type,
            'is_profile_complete'   =>  $data->is_profile_complete,
            'is_verified'           =>  $data->is_verified,
            'is_blocked'            =>  $data->is_blocked,
            'is_social'             =>  $data->is_social
        ];
    }

    private function user($data)
    {
        return [
            'id'                    =>  $data->id,
            'first_name'            =>  $data->first_name,
            'last_name'             =>  $data->last_name,
            'email'                 =>  $data->email,
            'user_type'             =>  $data->user_type,
            'profile_image'         =>  $data->profile_image,
            'phone_number'          =>  $data->phone_number,
            'zip_code'              =>  $data->zip_code,
            'is_profile_complete'   =>  $data->is_profile_complete,
            'is_verified'           =>  $data->is_verified,
            'is_blocked'            =>  $data->is_blocked,
            'is_social'             =>  $data->is_social,
            'social_media_links'    =>  $data->social_media_links
        ];
    }

    private function hat($data)
    {
        return [
            'id'                    =>  $data->id,
            'first_name'            =>  $data->first_name,
            'last_name'             =>  $data->last_name,
            'email'                 =>  $data->email,
            'user_type'             =>  $data->user_type,
            'profile_image'         =>  $data->profile_image,
            'phone_number'          =>  $data->phone_number,
            'bio'                   =>  $data->bio,
            'specialty'             =>  $data->specialty,
            'zip_code'              =>  $data->zip_code,
            'is_profile_complete'   =>  $data->is_profile_complete,
            'is_verified'           =>  $data->is_verified,
            'is_blocked'            =>  $data->is_blocked,
            'is_social'             =>  $data->is_social,
            'social_media_links'    =>  $data->social_media_links
        ];
    }
}
