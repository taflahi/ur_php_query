<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Webpatser\Uuid\Uuid;
use DateTime;

class EventController extends Controller
{
	public function test() {
    //     $query = '
    //         {
    //             "entityId": "Dx;9788498387568",
    //             "entityType": "item",
    //             "event": "$set",
    //             "eventTime": "2017-08-15T04:52:25.575+0000",
    //             "properties": {
    //                 "info": ["author:jkrowling", "price:12ish"],
    //                 "status": [1],
    //                 "detail": ["raw_price:IDR120000.00", "url:http://localhost:8000/products/harry-potter-and-the-cursed-child", "image:http://localhost:8000/media/cache/sylius_shop_product_large_thumbnail/08/1a/f773dfe9131ba76541a2a9d67211.jpeg", "name:Harry Potter and the Cursed Child"],
    //                 "businessId": ["Dx"]
    //             }
    //         }
    //     ';

    //     $query = '
    //        {
    //     	  "event" : "buy",
		  //     "entityType" : "user",
		  //     "entityId" : "Dx;3",
		  //     "targetEntityType" : "item",
		  //     "targetEntityId" : "Dx;9788498383621",
		  //     "eventTime" : "2017-08-16T07:05:08.813+0000"
		  // }';

        $query = '
            {
                "action" : "buy",
                "uid" : 3,
                "bid" : "Dx",
                "iid" : 4
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
        $query->entityId = $query->bid . ';' . $query->uid;
        $query->targetEntityType = 'item';
        $query->targetEntityId = $query->bid . ';' . $query->iid;
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