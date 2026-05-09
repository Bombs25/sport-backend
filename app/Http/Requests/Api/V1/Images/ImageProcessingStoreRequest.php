<?php

namespace App\Http\Requests\Api\V1\Images;

use App\Listeners\ImageProcessingListener;
use App\Rules\RasterImageFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Valide l’upload multipart d’un tableau de fichiers image (`File[]` côté front, champ `files`).
 *
 * La validation est exécutée dans {@see ImageProcessingListener} ; cette classe centralise les règles.
 *
 * Front : `FormData` avec plusieurs entrées `files[]` ou équivalent pour que Laravel reçoive `files` comme tableau.
 */
class ImageProcessingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesDefinition();
    }

    /**
     * Règles partagées (listener, tests, ou FormRequest HTTP si besoin).
     *
     * @return array<string, mixed>
     */
    public static function rulesDefinition(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:5120',
                new RasterImageFile,
            ],
        ];
    }

    /**
     * Valide `files` et renvoie la liste normalisée. Lève une {@see ValidationException} si invalide.
     *
     * @param  array<int|string, UploadedFile>  $files
     * @return list<UploadedFile>
     */
    public static function validatedFileList(array $files): array
    {
        $validator = Validator::make(
            ['files' => array_values($files)],
            self::rulesDefinition()
        );

        /** @var array{files: array<int, UploadedFile>} $validated */
        $validated = $validator->validate();

        return array_values($validated['files']);
    }

    /**
     * @return list<UploadedFile>
     */
    public function validatedFiles(): array
    {
        /** @var list<UploadedFile> $files */
        $files = $this->validated('files');

        return array_values($files);
    }
}
