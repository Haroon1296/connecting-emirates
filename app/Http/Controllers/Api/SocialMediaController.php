<?php

namespace App\Http\Controllers\Api;

use App\Enums\Event\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Event\ListResource;
use App\Models\{
    User,
    Event,
    Comment,
    URequest,
    EventType,
    EventOption,
    ShoutOutRequest,
    SocialMediaLink,
    EventComingUser,
    EventInterestedPeople
};
use ElephantIO\Client;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SocialMediaController extends Controller
{
    use ApiResponser;
    
    /** Event type list */
    public function eventTypeList(Request $request)
    {
        $eventType = EventType::orderBy('title')->select('id', 'title')->get();

        if(count($eventType) > 0){
            return $this->successDataResponse('Event types found successfully.', $eventType, 200);
        } else {
            return $this->errorResponse('No Event types found.', 400);
        }
    }

    /** Event list */
    public function eventList(Request $request)
    {
        $this->validate($request, [
            'offset'            =>   'required|numeric',
            'list_type'         =>   'required|in:up_coming,past,my_event'
        ]);

        $authId = auth()->user()->id;
        $date = now()->format('Y-m-d');

        $events = Event::with('user:id,first_name,last_name,profile_image', 'event_type:id,title', 'comments.user', 'event_options', 'coming_user.user')
                       ->withCount('u_request', 'shout_out_request', 'interested_people')

        ->when($request->list_type == 'up_coming', function($q) use ($date, $authId){
            return $q->whereDate('date_time', '>=', $date)
            ->when(auth()->user()->user_type == 'hat', function($q_2) use ($authId) {
                return $q_2->where('user_id', $authId);
            });
        })
        ->when($request->list_type == 'past', function($q) use ($date, $authId){
            return $q->whereDate('date_time', '<', $date)
            ->when(auth()->user()->user_type == 'hat', function($q_2) use ($authId) {
                return $q_2->where('user_id', $authId);
            });
        })
        ->when($request->list_type == 'my_event', function($q) use ($authId, $date){
            return $q->where('user_id', $authId)->whereDate('date_time', '>=', $date);
        })
        ->latest();

        $totalEvents = $events->count();

        $events = $events->skip($request->offset)
        ->take(10)
        ->get();

        $data = [
            'total_events' => $totalEvents,
            'events'       => ListResource::collection($events)
        ];

        if(count($events) > 0){
            return $this->successDataResponse('Events found successfully.', $data, 200);
        } else {
            return $this->errorResponse('No events found.', 400);
        }
    }

    /** Detail Event */
    public function eventDetail(Request $request)
    {
        $this->validate($request, [
            'event_id'     =>     'required|exists:events,id'
        ]);
        
        $event = Event::whereId($request->event_id)->withCount('u_request', 'shout_out_request', 'interested_people')->with('user:id,first_name,last_name,profile_image,user_type', 'event_type:id,title', 'comments.user', 'event_options', 'coming_user.user')->first();
        return $this->successDataResponse('Events found successfully.', new ListResource($event), 200);
    }

    /** Event create */
    public function eventCreate(Request $request)
    {
        $this->validate($request, [
            'event_type_id'             =>       'required|exists:event_types,id',
            'title'                     =>       'required',
            'description'               =>       'required',
            'date_time'                 =>       'required|date_format:Y-m-d H:i:s|after_or_equal:today',
            'thumbnail'                 =>       'required|mimes:jpeg,jpg,png',
            'venue_name'                =>       'required',
            'venue_address'             =>       'required',
            'latitude'                  =>       'required',
            'longitude'                 =>       'required',
            'state'                     =>       'required',
            'city'                      =>       'required',
            'zip_code'                  =>       'required',
            'event_option'              =>       'required|array',
            'event_option.*.type'       =>       'required|in:shout_out_request,u_request,m_request',
            'event_option.*.capacity'   =>       'required',
            'event_option.*.notes'      =>       'required'
        ]);

        try{
            $thumbnail_path = null;
            if($request->hasFile('thumbnail')){ 
                $thumbnail = $request->thumbnail->store('public/event');
                $thumbnail_path = Storage::url($thumbnail);
            }
            
            $code = strtotime(now());
            $data = $request->only(
                'event_type_id', 'title', 'description', 'date_time', 'venue_name', 'venue_address', 'latitude', 'longitude', 'state', 'city', 'zip_code'
                ) + ['user_id' => auth()->id(), 'thumbnail' => $thumbnail_path, 'code' => $code];

            $created = Event::create($data);

            if($created){
                if(count($request->event_option) > 0){
                    foreach($request->event_option as $key => $event_option){
                        EventOption::create($event_option + ['event_id' => $created->id]);
                    }
                }
            }
            
            $output_qr = '/storage/event/qr-code-' . $created->id . $this->generateRandomString() . '.png';
            QrCode::format('png')->size(200)->errorCorrection('H')->generate($code, public_path($output_qr));
            Event::whereId($created->id)->update(['qr_code' => $output_qr]);

            return $this->successDataResponse('Event created successfully.', ['code' => $code, 'qr_code' => isset($output_qr) ? $output_qr : null]);
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }        
    }

    /** Event update */
    public function eventUpdate(Request $request)
    {
        $this->validate($request, [
            'event_id'                  =>  'required|exists:events,id',
            'event_type_id'             =>   [Rule::requiredIf($request->has('event_type_id')), 'exists:event_types,id'],
            'title'                     =>   Rule::requiredIf($request->has('title')),
            'description'               =>   Rule::requiredIf($request->has('description')),
            'date_time'                 =>   [Rule::requiredIf($request->has('date_time')), 'date_format:Y-m-d H:i:s', 'after_or_equal:today'],
            'thumbnail'                 =>   [Rule::requiredIf($request->has('thumbnail')), 'mimes:jpeg,jpg,png'],
            'venue_name'                =>   Rule::requiredIf($request->has('venue_name')),
            'venue_address'             =>   Rule::requiredIf($request->has('venue_address')),
            'latitude'                  =>   Rule::requiredIf($request->has('latitude')),
            'longitude'                 =>   Rule::requiredIf($request->has('longitude')),
            'state'                     =>   Rule::requiredIf($request->has('state')),
            'city'                      =>   Rule::requiredIf($request->has('city')),
            'zip_code'                  =>   Rule::requiredIf($request->has('zip_code'))
        ]);

        try{
            $event = Event::whereId($request->event_id)->first();

            $thumbnail_path = $event->thumbnail;
            if($request->hasFile('thumbnail')){ 
                unlink(public_path($event->thumbnail));
                $thumbnail = $request->thumbnail->store('public/event');
                $thumbnail_path = Storage::url($thumbnail);
            }

            $data = $request->only(
                'event_type_id', 'title', 'description', 'date_time', 'venue_name', 'venue_address', 'latitude', 'longitude', 'state', 'city', 'zip_code'
            ) + ['thumbnail' => $thumbnail_path];

            $updated = Event::whereId($request->event_id)->update($data);
            if($updated && isset($request->event_option)){
                EventOption::where('event_id', $request->event_id)->delete();
                if(count($request->event_option) > 0){
                    foreach($request->event_option as $key => $event_option){
                        EventOption::create($event_option + ['event_id' => $request->event_id]);
                    }
                }
            }

            return $this->successResponse('Event updated successfully.');

        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }       
    }

    /** Event delete */
    public function eventDelete(Request $request)
    {
        $this->validate($request, [
            'event_id'    =>    'required|exists:events,id'
        ]); 

        try{
            DB::beginTransaction();
            $event = Event::whereId($request->event_id)->where(['user_id' => auth()->id()])->first();
            $thumbnail = $event->thumbnail;

            $event->delete();
            unlink(public_path($thumbnail));

            DB::commit();
            return $this->successResponse('Event deleted successfully.');
        } catch (\Exception $exception){
            DB::rollBack();
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Event create comment  */
    public function eventCreateComment(Request $request)
    {
        $this->validate($request, [
            'event_id'    =>    'required|exists:events,id',
            'comment'     =>    'required',
        ]); 

        try{
            $data = $request->only('event_id', 'comment') + ['user_id' => auth()->id()];
            Comment::create($data);
            return $this->successResponse('Comment created successfully.');
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Event interested */
    public function eventInterested(Request $request)
    {
        $this->validate($request, [
            'event_id'    =>    'required|exists:events,id'
        ]);

        try{
            $auth = auth()->user();
            $event_id = $request->event_id;

            if($auth->user_type == 'hat'){
                return $this->errorResponse('Only user and guest can be show interest in event.', 400);
            } else {
                $eventInterestedPeople = EventInterestedPeople::where(['user_id' => $auth->id, 'event_id' => $event_id])->exists();
    
                if($eventInterestedPeople){
                    return $this->errorResponse('You already interested in this event.', 400);
                } else {
                    EventInterestedPeople::create(['user_id' => $auth->id, 'event_id' => $event_id]);
                    return $this->successResponse('Interested successfully.');
                }
            }
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Event join */
    public function eventJoin(Request $request)
    {
        $this->validate($request, [
            'code'    =>    'required|exists:events,code'
        ]); 

        try{
            if(auth()->user()->user_type == 'hat'){
                return $this->errorResponse('Only user / guest can join event.', 400);
            } else {
                $event = Event::where(['code' => $request->code])->first();
                $eventComingUser = EventComingUser::where(['user_id' => auth()->id(), 'event_id' => $event->id])->exists();
    
                if($eventComingUser){
                    return $this->errorResponse('You already joined this event.', 400);
                } else {
                    EventComingUser::create([
                        'user_id'  => auth()->id(),
                        'event_id' => $event->id
                    ]);
                    return $this->successResponse('Event joined successfully.');
                }
            }
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        }
    }

    /** Create Shout Out Request */
    public function createShoutOutRequest(Request $request)
    {
        $this->validate($request, [
            'event_id'       =>    'required|exists:events,id',
            'category_id'    =>    'required|exists:event_types,id',
            'title'          =>    'required',
            'receiver'       =>    'required',
            'message'        =>    'required',
            'seat_number'    =>    'required'
        ]); 

        if(auth()->user()->user_type != 'hat'){
            try{
                $eventOption = EventOption::with('event')->where(['event_id' => $request->event_id, 'type' => 'shout_out_request'])->first();
                if(!empty($eventOption)){
                    $shoutOutRequest = ShoutOutRequest::where(['event_id' => $request->event_id, 'status' => RequestStatus::ACCEPT->value])->count();
    
                    if($shoutOutRequest >= $eventOption->capacity){
                        return $this->errorResponse('Shout out request limit has been reached.', 400);
                    } else {
                        $data = $request->only('event_id', 'title', 'category_id', 'receiver', 'message', 'seat_number') + ['user_id' => auth()->id()];
                        ShoutOutRequest::create($data);
    
                        // Socket
                        $this->socketEmit($request->event_id, $eventOption->event->user_id, 'shout_out_request'); 
    
                        // Notification
                        $receiver  = User::whereId($eventOption->event->user_id)->first();
    
                        $notification = [
                            'device_token'  => $receiver->device_token,
                            'sender_id'     => auth()->id(),
                            'receiver_id'   => $eventOption->event->user_id,
                            'description'   => auth()->user()->first_name . ' ' . auth()->user()->last_name . ' send you a Shout Out Request.',
                            'title'         => $eventOption->event->title,
                            'record_id'     => $request->event_id,
                            'type'          => 'shout_out_request',
                            'created_at'    => now(),
                            'updated_at'    => now()
                        ];
                        if ($receiver->device_token != null) {
                            push_notification($notification);
                        }
                        in_app_notification($notification);
                        // End Notification 
    
                        return $this->successResponse('Shout out request send successfully.');
                    }
                } else {
                    return $this->errorResponse('Hat can not be added shout outs.', 400);
                }
            } catch (\Exception $exception){
                return $this->errorResponse($exception->getMessage(), 400);
            } 
        } else {
            return $this->errorResponse('Hat can not be send shout out request.', 400);
        }
    }

    /** Create U Request */
    public function createURequest(Request $request)
    {
        $this->validate($request, [
            'event_id'      =>    'required|exists:events,id',
            'title'         =>    'required',
            'song'          =>    'required|mimes:map3,ogg,waw',
            'thumbnail'     =>    'required|mimes:jpeg,png,jpg'
        ]); 

        if(auth()->user()->user_type != 'hat'){
            try{
                $eventOption = EventOption::with('event')->where(['event_id' => $request->event_id, 'type' => 'u_request'])->first();
                if(!empty($eventOption)){
                    $uRequest = URequest::where(['event_id' => $request->event_id, 'status' => RequestStatus::ACCEPT->value])->count();

                    if($uRequest >= $eventOption->capacity){
                        return $this->errorResponse('U request limit has been reached.', 400);
                    } else {
                        $data = $request->only('event_id', 'title') + ['user_id' => auth()->id()];
            
                        if($request->hasFile('song')){
                            $song = $request->song->store('public/event/request');
                            $song_path = Storage::url($song);
                            $data['song'] = $song_path;
                        }
                
                        if($request->hasFile('thumbnail')){
                            $thumbnail = $request->thumbnail->store('public/event/request');
                            $thumbnail_path = Storage::url($thumbnail);
                            $data['thumbnail'] = $thumbnail_path;
                        }
                        $created = URequest::create($data);

                        if(count($request->social_media_title) > 0){ 
                            foreach($request->social_media_title as $key => $title){
                                $attachmentData['u_request_id'] = $created->id;
                                $attachmentData['title'] = $title;
                                $attachmentData['link'] = $request->social_media_link[$key];
                                SocialMediaLink::create($attachmentData);
                            }
                        }

                        // Socket
                        $this->socketEmit($request->event_id, $eventOption->event->user_id, 'u_request'); 
                        // Socket end
                        
                        // Notification
                        $receiver  = User::whereId($eventOption->event->user_id)->first();
    
                        $notification = [
                            'device_token'  => $receiver->device_token,
                            'sender_id'     => auth()->id(),
                            'receiver_id'   => $eventOption->event->user_id,
                            'description'   => auth()->user()->first_name . ' ' . auth()->user()->last_name . ' send you a U Request.',
                            'title'         => $eventOption->event->title,
                            'record_id'     => $request->event_id,
                            'type'          => 'u_request',
                            'created_at'    => now(),
                            'updated_at'    => now()
                        ];
                        if ($receiver->device_token != null) {
                            push_notification($notification);
                        }
                        in_app_notification($notification);
                        // End Notification 

                        return $this->successResponse('U request send successfully.');
                    }
                } else {
                    return $this->errorResponse('Hat can not be added u request.', 400);
                }
            } catch (\Exception $exception){
                return $this->errorResponse($exception->getMessage(), 400);
            } 
        } else {
            return $this->errorResponse('Hat can not be send U request.', 400);
        }
    }

    /** Accept / Reject Event Request */
    public function acceptRejectEventRequest(Request $request)
    {
        $this->validate($request, [
            'type'         =>   'required|in:shout_out_request,u_request,m_request',
            'id'           =>   [
                                    'required',
                                    Rule::when($request->type == 'shout_out_request', 'exists:shout_out_requests,id', ''), 
                                    Rule::when($request->type == 'u_request', 'exists:u_requests,id', '')
                                ],
            'status'       =>   ['required', new Enum(RequestStatus::class)],
            'user_id'      =>   'required|exists:users,id',
            'event_id'     =>   'required|exists:events,id'
        ]); 

        try{
            $type = $request->type;
            switch ($type) {
                case "u_request":
                    $update = URequest::whereId($request->id)->update(['status' => $request->status]);
                    break;
                case "shout_out_request":
                    $update = ShoutOutRequest::whereId($request->id)->update(['status' => $request->status]);
                    break;      
            }

            $replaceType = str_replace("_", " ",  $type);
            if($request->status == RequestStatus::ACCEPT->value){
                // Notification
                $receiver  = User::whereId($request->user_id)->first();
                
                $notification = [
                    'device_token'  => $receiver->device_token,
                    'sender_id'     => auth()->id(),
                    'receiver_id'   => $request->user_id,
                    'description'   => auth()->user()->first_name . ' ' . auth()->user()->last_name . ' accepted your ' . $replaceType,
                    'title'         => ucfirst($replaceType) . ' accepted',
                    'record_id'     => $request->event_id,
                    'type'          => $type.'_accepted',
                    'created_at'    => now(),
                    'updated_at'    => now()
                ];
                if ($receiver->device_token != null) {
                    push_notification($notification);
                }
                in_app_notification($notification);
                // End Notification 
            }
            
            return $this->successResponse(ucfirst($replaceType) . ' '. strtolower(RequestStatus::from($request->status)->name) . ' successfully.');
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        } 
    }

    /** My request */
    public function myRequest(Request $request)
    {
        $this->validate($request, [
            'type'      =>      'required|in:shout_out_request,u_request,m_request'
        ]); 

        $type = $request->type;
        $auth = auth()->user();

        switch ($type) {
            case "u_request":
                
                $getRequest = URequest::when($auth->user_type == 'hat', function($q) use ($auth) {
                    $q->whereHas('event', function($q_2) use ($auth) {
                        $q_2->where('user_id', $auth->id);
                    });
                })
                ->with(['user', 'event'])->when($auth->user_type != 'hat', function($q) use ($auth) {
                    $q->where('user_id', $auth->id);
                }) ->latest()->get();

                break;
            case "shout_out_request":
                $update = ShoutOutRequest::whereId($request->id)->update(['status' => $request->status]);
                break;      
        }

        return $getRequest;
    }

    /** Socket */
    private function socketEmit($event_id, $user_id, $type)
    {
        try{
            $client = new Client(Client::engine(Client::CLIENT_4X, $this->socket_url, $this->options));
            $client->initialize();
    
            $data = [
                "event_id"  =>  $event_id,
                "hat_id"    =>  $user_id,
                "type"      =>  $type
            ];
            $client->emit('my_event_request_list', $data);       
        } catch (\Exception $exception){
            return $this->errorResponse($exception->getMessage(), 400);
        } 
    }
    
    private function generateRandomString($length = 10) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
