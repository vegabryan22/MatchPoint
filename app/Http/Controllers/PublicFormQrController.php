<?php

namespace App\Http\Controllers;

use App\Enums\PublicFormType;
use App\Http\Requests\PublicForms\PublicFormQrRequest;
use App\Models\Tournament;
use App\Services\PublicFormQrService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class PublicFormQrController extends Controller
{
    public function __construct(private readonly PublicFormQrService $qrCodes) {}

    public function image(PublicFormQrRequest $request, Tournament $tournament, PublicFormType $form): Response
    {
        $data = $request->validated();
        $image = $this->qrCodes->render($tournament, $form, $data['format'], (int) $data['size']);
        $response = response($image['content'], 200, [
            'Content-Type' => $image['mime_type'],
            'Cache-Control' => 'private, max-age=300',
        ]);

        if ($data['download']) {
            $response->header('Content-Disposition', "attachment; filename=\"{$tournament->slug}-{$form->value}.{$image['extension']}\"");
        }

        return $response;
    }

    public function poster(Tournament $tournament, PublicFormType $form): View
    {
        Gate::authorize('managePublicForms', $tournament);
        $publicForm = $this->qrCodes->shareData($tournament, $form);
        abort_if($publicForm === null, 404);

        return view('public-forms.poster', [
            'tournament' => $tournament,
            'publicForm' => $publicForm,
        ]);
    }
}
