<?php

namespace App\Http\Controllers;

use App\Models\GitHubRawEvent;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GitHubController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Receives a raw event from GitHub.
     * 
     * @param Request $request The request instance we will be processing
     * 
     * @link https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads
     */
    public function receiveRawEvent(Request $request) {
        $eventId = $request->header('X-GitHub-Delivery'); // GitHub delivery GUID
        $eventName = $request->header('X-GitHub-Event'); // name of the GitHub event

        // retrieve the request payload as an associative array and then retrieve
        // the optional name of an event-specific action
        $payload = $request->input();
        $eventAction = (!empty($payload['action']) ? $payload['action'] : null);

        // attempt to instantiate the event using its delivery GUID or just retrieve
        // the existing instance
        $event = GitHubRawEvent::firstOrNew(
            [
                'event_id' => $eventId,
            ],
            [
                'event_name' => $eventName,
                'event_action' => $eventAction,
                'payload' => $payload,
            ]
        );

        // if the model already exists (and therefore the event was already delivered),
        // send back a 409 Conflict success response to indicate that this event is a
        // duplicate of a previous event
        if ($event->exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate GitHub raw event (delivery ID has been received previously)',
                'deliveryId' => $eventId,
                'event' => $eventName,
                'action' => $eventAction,
            ])->setStatusCode(Response::HTTP_CONFLICT);
        }

        // now actually save the event to persist it
        try {
            $success = $event->save();
            if (!$success) {
                // send back an error response since the save failed
                return response()->json([
                    'status' => 'error',
                    'message' => 'Received GitHub raw event but failed to save it',
                    'deliveryId' => $eventId,
                    'event' => $eventName,
                    'action' => $eventAction,
                ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (QueryException $e) {
            // some kind of general query exception
            return response()->json([
                'status' => 'error',
                'message' => 'Received GitHub raw event but an exception occurred while saving it: ' . $e->getMessage(),
                'deliveryId' => $eventId,
                'event' => $eventName,
                'action' => $eventAction,
            ])->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // successful operation so send back the success response
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully received the GitHub delivery and added the raw event',
            'deliveryId' => $eventId,
            'event' => $eventName,
            'action' => $eventAction,
        ]);
    }
}
