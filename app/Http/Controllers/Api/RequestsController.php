<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;

use App\Models\Request;
use App\Models\Timing;

class RequestsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  HttpRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(HttpRequest $httpRequest)
    {
        $timing = Timing::findOrFail($httpRequest->timing_id);

        $request = Request::where('user_id', $httpRequest->user()->id)
            ->where('timing_id', $timing->id)
            ->whereStatus('requested')
            ->first();

        if (!!$request) {
            abort(422);
        }

        $request = new Request();
        $request->user_id = $httpRequest->user()->id;
        $request->timing_id = $timing->id;
        $request->request = $httpRequest->comment;
        $request->requested_at = now();
        $request->save();

        return $timing;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  HttpRequest  $request
     * @param  Request  $form
     * @return \Illuminate\Http\Response
     */
    public function update(HttpRequest $httpRequest, Timing $request)
    {
        $timing = $request;

        if (!in_array($httpRequest->user()->id, [5, 6721])) {
            abort(403);
        }

        Request::where('timing_id', $timing->id)
            ->whereStatus('requested')
            ->update([
                'status' => $httpRequest->status,
                'response' => $httpRequest->comment,
                'reviewer_user_id' => $httpRequest->user()->id,
                'reviewed_at' => now()
            ]);

        if ($httpRequest->status == 'approved') {
            $timing->verifiers()->detach();
            $timing->verified = false;
            $timing->timestamps = false;
            $timing->save();
        }

        return [];
    }
}
