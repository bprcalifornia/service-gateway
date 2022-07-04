<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Response;

class GitHubEventMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @link https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads
     */
    public function handle($request, Closure $next)
    {
        // make sure the delivery ID and event name are present first
        $eventId = $request->header('X-GitHub-Delivery');
        $eventName = $request->header('X-GitHub-Event');

        if (empty($eventId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-GitHub-Delivery request header missing from GitHub raw event',
                'event' => (!empty($eventName) ? $eventName : null),
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        if (empty($eventName)) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-GitHub-Event request header missing from GitHub raw event',
                'deliveryId' => $eventId,
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // next, make sure the request has been sent as JSON
        if (!$request->isJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request must be sent with a JSON payload and a Content-Type header of application/json',
                'event' => $eventName,
                'deliveryId' => $eventId,
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // next, make sure we actually have a request payload so we didn't receive
        // an empty request
        $requestBody = $request->getContent();
        if (empty($requestBody)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request cannot have an empty body and no event payload was received',
                'event' => $eventName,
                'deliveryId' => $eventId,
            ])->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        // now validate the payload signature if we have are expecting to receive
        // signatures that leverage our secret
        $secret = env('GITHUB_RAW_EVENT_WEBHOOK_SECRET');
        if (!empty($secret)) {
            $requestSignature = $request->header('X-Hub-Signature-256');
            if (empty($requestSignature)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'X-Hub-Signature-256 request header missing from GitHub raw event',
                    'event' => $eventName,
                    'deliveryId' => $eventId,
                ])->setStatusCode(Response::HTTP_BAD_REQUEST);
            }

            // take off the sha256= prefix on the request header value
            $requestSignature = str_replace('sha256=', '', $requestSignature);

            // take the request body, hash it with SHA-256, and then run it through HMAC
            // using the configured secret as the key
            $signedRequestBody = hash_hmac('sha256', $requestBody, $secret);
            if (!hash_equals($signedRequestBody, $requestSignature)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request signature could not be validated using the provided X-Hub-Signature-256 header',
                    'event' => $eventName,
                    'deliveryId' => $eventId,
                    'receivedSig' => $requestSignature,
                    'generatedSig' => $signedRequestBody,
                ])->setStatusCode(Response::HTTP_UNAUTHORIZED);
            }
        }

        return $next($request);
    }
}
