<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Client;

class RecommendationController extends Controller
{
    private $es_url = 'http://localhost:9200/reco/';
    private $size = 5;

    public function test() {
        // $query = '{
        //             "user" : "Dx;8",
        //             "item" : "Dx;9788498383621",
        //             "fields": [
        //                 {
        //                     "name" : "businessId",
        //                     "value" : ["Dx"],
        //                     "bias" : -1
        //                 }
        //             ]
        //         }';
        $query = '{
                    "user" : "8",
                    "item" : "9788498383621",
                    "businessId" : "Dx"
                }';
        
        return $this->predict(json_decode($query));
    }

    public function show(Request $request)
    {
        return $this->predict((object) $request->json()->all());
    }

    public function predict($query){
        $query_built = $this->buildQuery($query);
        $response = $this->esQuery($query_built);

        $this->log($query->businessId);

        return $this->buildResponse($response);
    }

    public function buildResponse($response){
        $response_item = [
            "itemScores" => $response->hits->hits
        ];

        $item_scores = array();
        
        foreach ($response_item['itemScores'] as $item) {
            $single_item = array();
            
            foreach ($item->_source->detail as $detail) {
                $item_detail = explode(':', $detail, 2);
                $single_item[$item_detail[0]] = $item_detail[1];
            }
            array_push($item_scores, $single_item);
        }

        return $item_scores;
    }

    public function buildQuery($raw_query) {
        $query = $raw_query;
        $query->user = $raw_query->businessId . ';' . $raw_query->user;
        $query->item = $raw_query->businessId . ';' . $raw_query->item;
        if(!isset($query->fields)){
            $query->fields = array();
        }

        array_push($query->fields, $this->buildBusinessField($raw_query->businessId));

        $boostable_events = $this->getBiasedRecentUserActions($query);
        $boostable = $boostable_events[0];
        $events = $boostable_events[1];

        $should_query = $this->buildQueryShould($query, $boostable);
        $must_query = $this->buildQueryMust($query, $boostable);
        $must_not_query = $this->buildQueryMustNot($query, $events);
        $sort_query = $this->buildQuerySort();

        $query = [
            "size" => $this->size,
            "query" => [
                "bool" => [
                    "should" => $should_query,
                    "must" => $must_query,
                    "must_not" => $must_not_query,
                    "minimum_should_match" => 1
                ]
            ], "sort" => $sort_query
        ];

        return $query;
    }

    public function buildQueryShould($query, $boostable)
    {
        $recent_user_history = $boostable;
        $similar_items = $this->getBiasedSimilarItem($query);
        $boosted_meta_data = $this->getBoostedMetadata($query);

        $all_corellator = array_merge($recent_user_history, $similar_items, $boosted_meta_data);

        $should_query = array();
        foreach ($all_corellator as $correlator) {
            $terms = [
                'terms' => [
                    $correlator[0] => $correlator[1]
                ]
            ];

            array_push($should_query, $terms);
        }

        $constant_score = [
            "constant_score" => [
                "filter" => [
                    "match_all" => (object) null
                ],
                "boost" => 0
            ]
        ];

        array_push($should_query, $constant_score);


        return $should_query;
    }

    public function buildQueryMust($query, $boostable)
    {
        $filtering_meta_data = $this->getFilteringMetadata($query);

        $all_corellator = $filtering_meta_data;

        $must_query = array();
        foreach ($all_corellator as $correlator) {
            $terms = [
                'terms' => [
                    $correlator[0] => $correlator[1],
                    'boost' => 0
                ]
            ];

            array_push($must_query, $terms);
        }

        return $must_query;
    }

    public function buildQueryMustNot($query, $events)
    {
        $must_not_item = array();
        foreach ($events as $event) {
            if($event->event == 'buy' 
                && isset($event->targetEntityId)
                && !in_array($event->targetEntityId, $must_not_item)){
                array_push($must_not_item, $event->targetEntityId);
            }
        }
        if($query->item){
            array_push($must_not_item, $query->item);
        }

        $must_not_query = array();
        $terms = [
            'ids' => [
                'values' => $must_not_item,
                'boost' => 0
            ]
        ];

        array_push($must_not_query, $terms);

        return $must_not_query;
    }

    public function buildQuerySort(){
        $sort = [
            ["_score" => [
                "order" => "desc"
            ]], ["popRank" => [
                "unmapped_type" => "double",
                "order" => "desc"
            ]]
        ];

        return $sort;
    }

    public function getBiasedRecentUserActions($query)
    {
        $response = [
            ['buy', [], 0],
            ['view', [], 0]
        ];

        $events = array();

        if(isset($query->user)){
            $user_id = $query->user;
            $db = app('db');

            $eventDB = $db->connection('recommendation');
            $events = $eventDB->table('pio_event_1')->where([
                ['entityId', '=' , $user_id],
                ['entityType', '=' , 'user']
                ])->whereIn('event', ['buy', 'view'])->latest('eventTime')->get();

            $buy_events = array();
            $view_events = array();
            foreach ($events as $p) {
                if($p->event == 'buy' && !in_array($p->targetEntityId, $buy_events)){
                    array_push($buy_events, $p->targetEntityId);
                }
                else if ($p->event == 'view' && !in_array($p->targetEntityId, $view_events)){
                    array_push($view_events, $p->targetEntityId);
                }
            }

            $response = [
                ['buy', array_slice($buy_events, 0, 2000), 0],
                ['view', array_slice($view_events, 0, 2000), 0]
            ];   
        }

        return [$response, $events];
    }

    public function getBiasedSimilarItem($query)
    {
        $response = [
            ['buy', [], 0],
            ['view', [], 0]
        ];

        if(isset($query->item)){
            $item_id = $query->item;
            $item = $this->getItem($item_id);
            $buy_events = $item->_source->buy;
            $view_events = $item->_source->view;

            $response = [
                ['buy', array_slice($buy_events, 0, 2000), 0],
                ['view', array_slice($view_events, 0, 2000), 0]
            ];
        }
        
        return $response;
    }

    public function getBoostedMetadata($query)
    {
        $response = array();
        if(isset($query->fields)){
            $fields = $query->fields;
            foreach ($fields as $field) {
                if($field->bias > 0.0){
                    $single_field = [$field->name, $field->value, $field->bias];
                    array_push($response, $single_field);
                }
            }
        }

        return $response;
    }

    public function getFilteringMetadata($query)
    {
        $response = array();
        if(isset($query->fields)){
            $fields = $query->fields;
            foreach ($fields as $field) {
                if($field->bias < 0){
                    $single_field = [$field->name, $field->value, $field->bias];
                    array_push($response, $single_field);
                }
            }
        }

        return $response;
    }

    public function buildBusinessField($business_id)
    {
        return json_decode(json_encode(
                [
                    "name" => "businessId",
                    "value" => [$business_id],
                    "bias" => -1
                ]
            ));
    }

    public function getItem($item_id)
    {
        $client = new Client();
        $res = $client->request('GET', $this->es_url . 'items/' . $item_id);
        
        return json_decode($res->getBody());
    }

    public function esQuery($query){
        $client = new Client();
        $res = $client->post($this->es_url . '_search', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($query)
            ]);
        
        return json_decode($res->getBody());
    }

    public function log($businessId){
        $db = app('db');
        $businessDB = $db->connection('business');

        // dd($query);

        $businessDB->table('request_log')->insert([
            [
                'businessId' => $businessId
            ]
        ]);
    }
}