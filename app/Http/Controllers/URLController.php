<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class URLController extends Controller
{
    /**
     * Retrieve the user for the given ID.
     *
     * @param  string  $id
     * @return Response
     */
    public function show($hash_id)
    {
        $product = DB::table('product')->where('hashid', $hash_id)->first();

        if(is_null($product) || is_null($product->url)){
            abort(404, 'URL Not Found');
        }

        return redirect($product->url);
    }
}