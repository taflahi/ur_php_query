<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Webpatser\Uuid\Uuid;
use DateTime;

class EventController extends Controller
{
	public function test() {
        $query = '
            {
                "action" : "buy",
                "user" : 3,
                "businessId" : "Dx",
                "item" : 4
            }
        ';
        
        // return $this->insert($this->completeQuery(json_decode($query)));
        return $this->insert($this->completeQuery($this->simpleQuery(json_decode($query))));
    }

    public function show(Request $request)
    {
        return $this->insert($this->completeQuery((object) $request->json()->all()));
    }

    public function simpleShow(Request $request)
    {
        return $this->insert($this->completeQuery($this->simpleQuery((object) $request->json()->all())));
    }

    public function simpleQuery($query){
        // action, bid, uid, iid

        $datetime = new DateTime();

        $query->event = $query->action;
        $query->entityType = 'user';
        $query->entityId = $query->businessId . ';' . $query->user;
        $query->targetEntityType = 'item';
        $query->targetEntityId = $query->businessId . ';' . $query->item;
        $query->eventTime = $datetime->format(DateTime::ATOM);

        return $query;
    }

    public function completeQuery($query){
        if(!isset($query->properties)){
            $query->properties = '{}';
        } else {
            $query->properties = str_replace('\\', '', json_encode($query->properties));
        }

        if(!isset($query->targetEntityId)){
            $query->targetEntityId = NULL;
        }

        if(!isset($query->targetEntityType)){
            $query->targetEntityType = NULL;
        }

        return $query;
    }


	public function insert($query){
		$db = app('db');
        $eventDB = $db->connection('recommendation');

        // dd($query);

        $eventDB->table('pio_event_1')->insert([
		    [
                'id' => str_replace('-', '', Uuid::generate(4)),
		    	'event' => $query->event, 
		    	'entityType' => $query->entityType,
		    	'entityId' => $query->entityId,
                'targetEntityId' => $query->targetEntityId,
                'targetEntityType' => $query->targetEntityType,
                'properties' => $query->properties,
                'eventTime' => $this->parseDate($query->eventTime),
                'eventTimeZone' => 'UTC',
                'creationTimeZone' => 'UTC'
		    ]
		]);

        return response()->json([
                'ack' => 'true'
            ]);
	}

    private function parseDate($isoDate)
    {
        $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $isoDate);
        return $datetime;
    }
}