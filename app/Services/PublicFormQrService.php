<?php

namespace App\Services;

use App\Enums\PublicFormType;
use App\Models\Tournament;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicFormQrService
{
    public function shareData(Tournament $tournament, PublicFormType $type): ?array
    {
        if (! $type->isEnabled($tournament)) {
            return null;
        }

        $url = $this->url($tournament, $type);

        return [
            'type' => $type,
            'url' => $url,
            'is_local' => in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost'], true),
        ];
    }

    public function url(Tournament $tournament, PublicFormType $type): string
    {
        $this->ensureEnabled($tournament, $type);

        return route($type->routeName(), $tournament);
    }

    public function render(Tournament $tournament, PublicFormType $type, string $format, int $size): array
    {
        $qrCode = new QrCode(
            data: $this->url($tournament, $type),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(13, 17, 29),
            backgroundColor: new Color(255, 255, 255),
        );
        $writer = $format === 'png' ? new PngWriter : new SvgWriter;
        $result = $writer->write($qrCode);

        return [
            'content' => $result->getString(),
            'mime_type' => $result->getMimeType(),
            'extension' => $format,
        ];
    }

    private function ensureEnabled(Tournament $tournament, PublicFormType $type): void
    {
        if (! $type->isEnabled($tournament)) {
            throw new NotFoundHttpException('El formulario público no está habilitado.');
        }
    }
}
