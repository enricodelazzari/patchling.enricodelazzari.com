<?php

namespace App\Support;

use App\Enums\Verdict;
use App\Models\Result;
use GdImage;

/**
 * Draws the 1200×630 image used in social link previews, in the game's light palette.
 */
class ResultCard
{
    private const int WIDTH = 1200;

    private const int HEIGHT = 630;

    private const array COLORS = [
        'bg' => 0xE9EEEC,
        'surface' => 0xF7FAF9,
        'ink' => 0x1E2A32,
        'muted' => 0x55636B,
        'line' => 0xC9D3D0,
        'signal' => 0xF2C230,
        'danger' => 0xC8402F,
        'ok' => 0x2F7D5B,
        'body' => 0x8FC1B0,
        'screen' => 0xE8F5EF,
        'infected' => 0x9A6FB0,
    ];

    private GdImage $image;

    /** @var array<string, int> */
    private array $palette = [];

    public function render(Result $result, int $rank): string
    {
        $this->image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imageantialias($this->image, true);

        foreach (self::COLORS as $name => $hex) {
            $this->palette[$name] = imagecolorallocate($this->image, $hex >> 16, ($hex >> 8) & 0xFF, $hex & 0xFF);
        }

        imagefilledrectangle($this->image, 0, 0, self::WIDTH, self::HEIGHT, $this->palette['bg']);
        $this->tape(0);
        $this->tape(self::HEIGHT - 20);

        $this->text('PATCHLING', 24, 80, 92, 'muted', bold: true);
        $this->fittedText($result->verdict->label(), 76, 80, 190, 700, $result->verdict->completed() ? 'ink' : 'danger');
        $this->text(number_format($result->score).' points', 64, 80, 290, 'ink', bold: true);

        $byline = ($result->name ?? 'Anonymous').($result->name !== null ? '  ·  #'.$rank.' on the leaderboard' : '');
        $this->fittedText($byline, 30, 80, 350, 700, 'muted', bold: false);

        $stats = [
            ['€'.$result->revenue.'k', 'Revenue'],
            [$result->months.'/36', 'Months'],
            [(string) $result->features, 'Features'],
            [(string) $result->breaches, 'Attacks suffered'],
        ];

        foreach ($stats as $i => [$value, $label]) {
            $this->stat(80 + $i * 255, 420, $value, $label);
        }

        $this->pet(880, 90, $result->verdict);

        ob_start();
        imagepng($this->image);

        return ob_get_clean();
    }

    /**
     * The yellow-and-black hazard stripe from the game's .tape element.
     */
    private function tape(int $y): void
    {
        imagefilledrectangle($this->image, 0, $y, self::WIDTH, $y + 19, $this->palette['ink']);
        imagesetclip($this->image, 0, $y, self::WIDTH, $y + 19);

        for ($x = -20; $x < self::WIDTH + 20; $x += 40) {
            imagefilledpolygon($this->image, [$x, $y + 20, $x + 20, $y + 20, $x + 40, $y, $x + 20, $y], $this->palette['signal']);
        }

        imagesetclip($this->image, 0, 0, self::WIDTH - 1, self::HEIGHT - 1);
    }

    private function stat(int $x, int $y, string $value, string $label): void
    {
        $this->roundedRect($x, $y, $x + 235, $y + 120, 16, 'line');
        $this->roundedRect($x + 2, $y + 2, $x + 233, $y + 118, 14, 'surface');
        $this->fittedText($value, 40, $x + 20, $y + 60, 195, 'ink');
        $this->text($label, 20, $x + 20, $y + 96, 'muted');
    }

    /**
     * A still frame of the game's mascot, with the mood the verdict deserves.
     */
    private function pet(int $x, int $y, Verdict $verdict): void
    {
        $body = $verdict === Verdict::Crashed ? 'infected' : 'body';

        imagesetthickness($this->image, 6);
        imagefilledrectangle($this->image, $x + 117, $y, $x + 123, $y + 40, $this->palette['ink']);
        imagefilledellipse($this->image, $x + 120, $y, 26, 26, $this->palette[$verdict->completed() ? 'ok' : 'danger']);

        $this->roundedRect($x, $y + 36, $x + 240, $y + 290, 50, 'ink');
        $this->roundedRect($x + 6, $y + 42, $x + 234, $y + 284, 45, $body);
        $this->roundedRect($x + 34, $y + 70, $x + 206, $y + 190, 24, 'ink');
        $this->roundedRect($x + 39, $y + 75, $x + 201, $y + 185, 20, 'screen');

        $eyeHeight = $verdict->completed() ? 28 : 8;
        imagefilledellipse($this->image, $x + 85, $y + 120, 20, $eyeHeight, $this->palette['ink']);
        imagefilledellipse($this->image, $x + 155, $y + 120, 20, $eyeHeight, $this->palette['ink']);

        [$start, $end, $mouthY] = $verdict === Verdict::Healthy || $verdict === Verdict::Survived ? [20, 160, $y + 140] : [200, 340, $y + 175];
        imagearc($this->image, $x + 120, $mouthY, 70, 40, $start, $end, $this->palette['ink']);

        imagesetthickness($this->image, 1);
        imagefilledrectangle($this->image, $x + 40, $y + 215, $x + 110, $y + 225, $this->palette['ink']);
        imagefilledrectangle($this->image, $x + 40, $y + 237, $x + 90, $y + 247, $this->palette['ink']);
        imagefilledellipse($this->image, $x + 190, $y + 231, 24, 24, $this->palette['ink']);
    }

    private function roundedRect(int $x1, int $y1, int $x2, int $y2, int $r, string $color): void
    {
        $c = $this->palette[$color];
        imagefilledrectangle($this->image, $x1 + $r, $y1, $x2 - $r, $y2, $c);
        imagefilledrectangle($this->image, $x1, $y1 + $r, $x2, $y2 - $r, $c);

        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($this->image, $cx, $cy, $r * 2, $r * 2, $c);
        }
    }

    private function text(string $text, int $size, int $x, int $y, string $color, bool $bold = false): void
    {
        imagettftext($this->image, $size, 0, $x, $y, $this->palette[$color], $this->font($bold), $text);
    }

    /**
     * Draw text at the given size, shrinking it until it fits within $maxWidth.
     */
    private function fittedText(string $text, int $size, int $x, int $y, int $maxWidth, string $color, bool $bold = true): void
    {
        while ($size > 12) {
            $box = imagettfbbox($size, 0, $this->font($bold), $text);

            if ($maxWidth >= $box[2] - $box[0]) {
                break;
            }

            $size -= 2;
        }

        $this->text($text, $size, $x, $y, $color, $bold);
    }

    private function font(bool $bold): string
    {
        return resource_path('fonts/AtkinsonHyperlegible-'.($bold ? 'Bold' : 'Regular').'.ttf');
    }
}
