<?php

namespace App\Http\Requests\Api\V1\Posts\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;

trait ValidatesPostPublication
{
    protected function publicationExistsRule(string $idField = 'post_id', string $typeField = 'post_type'): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($idField, $typeField): void {
            $publicationType = (string) $this->input($typeField);
            if (! in_array($publicationType, ['regular', 'automatic'], true)) {
                return;
            }

            $table = $publicationType === 'regular' ? 'posts' : 'match_results';
            $exists = DB::table($table)
                ->where('id', (int) $this->input($idField, $value))
                ->exists();

            if (! $exists) {
                $fail('Publication introuvable pour ce type de post.');
            }
        };
    }

    protected function commentExistsForPublicationRule(string $publicationIdField = 'post_id', string $publicationTypeField = 'post_type'): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($publicationIdField, $publicationTypeField): void {
            $exists = DB::table('comments')
                ->where('id', (int) $value)
                ->where('publication_id', (int) $this->input($publicationIdField))
                ->where('publication_type', (string) $this->input($publicationTypeField))
                ->exists();

            if (! $exists) {
                $fail('Commentaire introuvable pour cette publication.');
            }
        };
    }

    protected function responseExistsForCommentRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $exists = DB::table('response_commentaires')
                ->where('id', (int) $value)
                ->where('comment_id', (int) $this->input('comment_id'))
                ->exists();

            if (! $exists) {
                $fail('Réponse introuvable pour ce commentaire.');
            }
        };
    }
}
