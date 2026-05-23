<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Support\SupportContactRequest;
use App\Mail\SupportContactMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Ce qu'il fait : envoie un mail au support depuis l'app mobile
 * (Paramètres > Centre d'aide > Contacter le support).
 *
 * Pourquoi : pas de table de tickets pour démarrer — un mail vers l'adresse
 * support (`config('mail.support_to')`) suffit. Le mail porte le `replyTo` de
 * l'utilisateur pour qu'une simple réponse mail revienne à lui.
 */
class SupportContactController extends Controller
{
    public function __invoke(SupportContactRequest $request): JsonResponse
    {
        $user = $request->user();

        $to = (string) config('mail.support_to', config('mail.from.address'));

        Mail::to($to)->queue(new SupportContactMail(
            category: $request->validated('category'),
            subjectLine: $request->validated('subject'),
            messageBody: $request->validated('message'),
            senderName: $user->name ?? 'Utilisateur',
            senderEmail: $user->email,
            senderId: (int) $user->id,
        ));

        return response()->json([
            'message' => __('Votre message a bien été transmis au support.'),
        ]);
    }
}
