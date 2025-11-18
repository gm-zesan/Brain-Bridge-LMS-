<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;

class TransactionController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/transactions",
     *      operationId="getTransactionsList",
     *      tags={"Transactions"},
     *      summary="Get list of all transactions",
     *      description="Returns all transactions from the database",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     * )
     */
    public function index()
    {
        $transactions = Transaction::with(['user', 'videoLesson'])->latest()->get();
        return response()->json($transactions);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/stripe/initiate",
     *     operationId="initiateStripePayment",
     *     tags={"Transactions"},
     *     summary="Initiate a Stripe payment",
     *     description="Creates a Stripe Checkout Session for payment",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","course_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Stripe Checkout Session created successfully"),
     * )
     */
    public function initiateStripePayment(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:video_lessons,id',
            'lesson_session_id' => 'nullable|exists:lesson_sessions,id',
        ]);

        if (isset($data['lesson_session_id'])) {
            $lessonSession = LessonSession::findOrFail($data['lesson_session_id']);
        }

        if (isset($data['course_id'])) {
            $course = Course::findOrFail($data['course_id']);
        }



        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => env('STRIPE_CURRENCY', 'usd'),
                        'product_data' => [
                            'name' => $course ? $course->title : 'One to One Lesson',
                        ],
                        'unit_amount' => $course ? intval($course->price * 100) : intval($lessonSession->price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => url('/api/transactions/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/api/transactions/cancel'),
            ]);

            // Temporary create a transaction (status pending)
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'course_id' => $course->id ?? null,
                'lesson_session_id' => $lessonSession->id ?? null,
                'amount' => $course ? $course->price : $lessonSession->price,
                'currency' => env('STRIPE_CURRENCY', 'usd'),
                'type' => 'payment',
                'provider' => 'Stripe',
                'provider_reference' => $session->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/success",
     *     operationId="stripePaymentSuccess",
     *     tags={"Transactions"},
     *     summary="Handle successful Stripe payment",
     *     @OA\Parameter(
     *         name="session_id",
     *         in="query",
     *         required=true,
     *         description="Stripe Checkout Session ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Payment successful"),
     * )
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $session = StripeSession::retrieve($sessionId);

            $transaction = Transaction::where('provider_reference', $sessionId)->first();

            if ($transaction && $transaction->status !== 'completed') {
                $transaction->update(['status' => 'completed']);
            }

            return response()->json([
                'message' => 'Payment successful!',
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to verify payment.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/cancel",
     *     operationId="stripePaymentCancel",
     *     tags={"Transactions"},
     *     summary="Handle canceled Stripe payment",
     *     @OA\Response(response=200, description="Payment canceled"),
     * )
     */
    public function cancel()
    {
        return response()->json(['message' => 'Payment canceled.']);
    }


    /**
     * @OA\Post(
     *     path="/api/transactions/stripe/webhook",
     *     operationId="handleStripeWebhook",
     *     tags={"Transactions"},
     *     summary="Handle Stripe webhook events",
     *     @OA\Response(response=200, description="Webhook handled successfully"),
     * )
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe Webhook Invalid Payload');
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe Webhook Invalid Signature');
            return response('Invalid signature', 400);
        }

        // Handle only successful checkout events
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $transaction = Transaction::where('provider_reference', $session->id)->first();
            if ($transaction && $transaction->status !== 'completed') {
                $transaction->update(['status' => 'completed']);
                Log::info('Transaction marked as completed via webhook', ['id' => $transaction->id]);
            }
        }

        return response('Webhook handled', 200);
    }


    /**
     * @OA\Post(
     *     path="/api/transactions/manual",
     *     operationId="manualTransaction",
     *     tags={"Transactions"},
     *     summary="Record a manual transaction",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","amount","type"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="course_id", type="integer", example=1),
     *             @OA\Property(property="lesson_session_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="number", format="float", example=49.99),
     *             @OA\Property(property="currency", type="string", example="usd"),
     *             @OA\Property(property="type", type="string", example="payment")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Manual transaction recorded successfully"),
     * )
     */
    public function manualStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:video_lessons,id',
            'lesson_session_id' => 'nullable|exists:lesson_sessions,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'type' => 'required|in:payment,refund',
        ]);

        $data['provider'] = 'Manual';
        $data['provider_reference'] = 'TXN-' . strtoupper(uniqid());
        $data['status'] = 'completed';

        $transaction = Transaction::create($data);

        return response()->json([
            'message' => 'Manual transaction recorded successfully.',
            'transaction' => $transaction
        ], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/transactions/{id}",
     *      operationId="getTransactionById",
     *      tags={"Transactions"},
     *      summary="Get transaction information",
     *      description="Returns transaction data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      )
     * )
     */
    public function show(Transaction $transaction)
    {
        return response()->json($transaction->load(['user', 'videoLesson']));
    }

    /**
     * @OA\Delete(
     *      path="/api/transactions/{id}",
     *      operationId="deleteTransaction",
     *      tags={"Transactions"},
     *      summary="Delete a transaction",
     *      description="Deletes a transaction by ID",
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Transaction deleted successfully"
     *      )
     * )
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted']);
    }
}
