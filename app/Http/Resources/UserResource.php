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
        if($this->user_type == 'customer'){
            return $this->customer($this);
        } else if($this->user_type == 'business') {
            return $this->business($this);
        } else {
            return [];
        }
    }
    private function customer($data)
    {
        return [
            'id'                    =>  $data->id,
            'full_name'             =>  $data->full_name,
            'email'                 =>  $data->email,
            'user_type'             =>  $data->user_type,
            'profile_image'         =>  $data->profile_image,
            'phone_number'          =>  $data->phone_number,
            'location'              =>  $data->location,
            'latitude'              =>  $data->latitude,
            'longitude'             =>  $data->longitude,
            'country_code'          =>  $data->country_code,
            'about'                 =>  $data->about,
            'date_of_birth'         =>  $data->date_of_birth,
            'gender'                =>  $data->gender,
            'emirates'              =>  $data->emirates,
            'nationality'           =>  $data->nationality,
            'customer_id'           =>  $data->customer_id,
            'push_notification'     =>  $data->push_notification,
            'is_profile_complete'   =>  $data->is_profile_complete,
            'is_verified'           =>  $data->is_verified,
            'is_blocked'            =>  $data->is_blocked,
            'is_social'             =>  $data->is_social,
            'is_deleted'            =>  $data->is_deleted
        ];
    }

    private function business($data)
    {
        return [
            'id'                    =>  $data->id,
            'full_name'             =>  $data->full_name,
            'email'                 =>  $data->email,
            'user_type'             =>  $data->user_type,
            'profile_image'         =>  $data->profile_image,
            'menu_image'            =>  $data->menu_image,
            'license_image'         =>  $data->license_image,
            'phone_number'          =>  $data->phone_number,
            'location'              =>  $data->location,
            'latitude'              =>  $data->latitude,
            'longitude'             =>  $data->longitude,
            'country_code'          =>  $data->country_code,
            'about'                 =>  $data->about,
            'registering_as'        =>  $data->registering_as,
            'operting_emirates'     =>  $data->operting_emirates,
            'category'              =>  $data->category,
            'venue_seating'         =>  $data->venue_seating,
            'dietary'               =>  $data->dietary,
            'customer_id'           =>  $data->customer_id,
            'push_notification'     =>  $data->push_notification,
            'is_profile_complete'   =>  $data->is_profile_complete,
            'is_verified'           =>  $data->is_verified,
            'is_admin_verified'     =>  $data->is_admin_verified,
            'is_blocked'            =>  $data->is_blocked,
            'is_social'             =>  $data->is_social,
            'is_deleted'            =>  $data->is_deleted,
            'venue_type'            =>  $data->venue_type
        ];
    }
}
