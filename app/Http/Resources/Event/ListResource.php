<?php

namespace App\Http\Resources\Event;

use App\Http\Resources\Event\UserResource;
use App\Models\EventComingUser;
use App\Models\EventInterestedPeople;
use App\Models\Favourite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        =>  $this->id,
            'title'                     =>  $this->title,
            'description'               =>  $this->description,
            'date_time'                 =>  $this->date_time,
            'thumbnail'                 =>  $this->thumbnail,
            'qr_code'                   =>  $this->qr_code,
            'code'                      =>  $this->code,
            'venue_name'                =>  $this->venue_name,
            'venue_address'             =>  $this->venue_address,
            'longitude'                 =>  $this->longitude,
            'latitude'                  =>  $this->latitude,
            'city'                      =>  $this->city,
            'state'                     =>  $this->state,
            'zip_code'                  =>  $this->zip_code,
            'is_interested'             =>  $this->is_interested($this->id, auth()->id()),
            'interested_people_count'   =>  $this->interested_people_count,
            'is_joined'                 =>  $this->is_joined($this->id, auth()->id()),
            'is_favourite'              =>  $this->is_favourite($this->id, auth()->id()),
            'created_at'                =>  $this->created_at,
            'u_request_count'           =>  $this->u_request_count,
            'shout_out_request_count'   =>  $this->shout_out_request_count,
            'created_by'                =>  $this->user,
            'event_type'                =>  $this->event_type,
            'event_options'             =>  $this->event_options,
            'comments'                  =>  $this->comments,
            'coming_user'               =>  UserResource::collection($this->coming_user)            
        ];
    }

    private function is_favourite($event_id, $auth_id)
    {
        return Favourite::where(['record_id' => $event_id, 'user_id' => $auth_id, 'type' => 'event'])->count();
    }

    private function is_interested($event_id, $auth_id)
    {
        return EventInterestedPeople::where(['event_id' => $event_id, 'user_id' => $auth_id])->count();
    }

    private function is_joined($event_id, $auth_id)
    {
        return EventComingUser::where(['event_id' => $event_id, 'user_id' => $auth_id])->count();
    }
}
