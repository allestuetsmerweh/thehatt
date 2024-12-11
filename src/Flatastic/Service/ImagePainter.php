<?php

namespace App\Flatastic\Service;

use Symfony\Component\HttpFoundation\Response;

class InvalidFormatException {
}

class ImagePainter {
    public function getImageResponse($image, $format) {
        try {
            $content = $this->paint($image, $format);
        } catch (InvalidFormatException $th) {
            throw new NotFoundHttpException("Invalid format: {$format}");
        }
        $response = new Response($content);
        $mime_by_format = [
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
        ];
        $mime = $mime_by_format[$format] ?? null;
        if ($mime !== null) {
            $response->headers->set('Content-Type', $mime_by_format[$format]);
        }
        return $response;
    }

    public function paint($image, $format) {
        if ($format === 'svg') {
            return $this->paintSvg($image);
        }
        if ($format === 'png') {
            return $this->paintPng($image);
        }
        throw new InvalidFormatException("Invalid format: {$format}");
    }

    private function paintSvg($image) {
        $width = $image['width'];
        $height = $image['height'];
        $instructions = $image['instructions'];

        $svg_out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $svg_out .= "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\">\n";
        foreach ($instructions as $instruction) {
            if ($instruction['type'] === 'text') {
                $x = $instruction['x'];
                $y = $instruction['y'];
                $font_size = $instruction['font_size'];
                $text = $instruction['text'];
                $svg_out .= "<text x=\"{$x}\" y=\"{$y}\" font-size=\"{$font_size}px\" font-family=\"sans-serif\">{$text}</text>\n";
            }
        }
        $svg_out .= "</svg>\n";
        return $svg_out;
    }

    private function paintPng($image) {
        $width = $image['width'];
        $height = $image['height'];
        $instructions = $image['instructions'];

        $img = imagecreatetruecolor($width, $height);
        $transparent = imagecolortransparent($img);
        imagefill($img, 0, 0, $transparent);
        $default_color = imagecolorallocate($img, 0x00, 0x00, 0x00);
        foreach ($instructions as $instruction) {
            if ($instruction['type'] === 'text') {
                $x = $instruction['x'];
                $y = $instruction['y'];
                $font_size = $instruction['font_size'];
                $php_font_size = $this->getPhpFont($font_size);
                $php_y = $y - ($font_size * 2 / 3);
                $text = $instruction['text'];
                $color = $this->parseColor($instruction['color'] ?? null);
                $img_color = imagecolorallocatealpha($img, $color[0], $color[1], $color[2], intval($color[3] * 127));
                imagestring($img, $php_font_size, $x, $php_y, $text, $img_color);
            }
        }
        $temp_file = tempnam(sys_get_temp_dir(), 'png_');
        imagepng($img, $temp_file);
        imagedestroy($img);

        $png_content = file_get_contents($temp_file);
        unlink($temp_file);
        return $png_content;
    }

    private function getPhpFont($font_size) {
        if ($font_size < 10) {
            return 1;
        }
        if ($font_size < 12) {
            return 2;
        }
        if ($font_size < 14) {
            return 3;
        }
        if ($font_size < 16) {
            return 4;
        }
        if ($font_size < 18) {
            return 5;
        }
        return 1;
    }

    private function parseColor($color_definition) {
        if (!$color_definition) {
            return [0x00, 0x00, 0x00, 0];
        }
        $rgba_pattern = '/^rgba\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\,\s*([0-9]+)\s*\)$/';
        if (preg_match($rgba_pattern, $color_definition, $matches)) {
            return [
                intval($matches[1]),
                intval($matches[2]),
                intval($matches[3]),
                floatval($matches[4]),
            ];
        }
        $rgb_pattern = '/^rgb\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\)$/';
        if (preg_match($rgba_pattern, $color_definition, $matches)) {
            return [
                intval($matches[1]),
                intval($matches[2]),
                intval($matches[3]),
                0.0,
            ];
        }
    }

    public function planSimpleTable($table_data) {
        $font_size = 16;
        $width_per_char = $font_size * 0.8;
        $width_per_column = 10;
        $height_per_row = $font_size + 4;
        $x_offset = 5;
        $y_offset = 14;

        $col_widths = [];
        foreach ($table_data as $row) {
            foreach ($row as $index => $value) {
                $col_widths[$index] = max($col_widths[$index] ?? 0, strlen($value));
            }
        }

        $width = 0;
        foreach ($col_widths as $col_width) {
            $width += $col_width * $width_per_char + $width_per_column;
        }
        $height = count($table_data) * $height_per_row;

        $instructions = [];
        $y = $y_offset;
        foreach ($table_data as $row_index => $row) {
            $x = $x_offset;
            foreach ($row as $col_index => $value) {
                $instructions[] = [
                    'type' => 'text',
                    'x' => $x,
                    'y' => $y,
                    'font_size' => $font_size,
                    'text' => $value,
                ];
                $x += $col_widths[$col_index] * $width_per_char + $width_per_column;
            }
            $y += $height_per_row;
        }

        return [
            'width' => $width,
            'height' => $height,
            'instructions' => $instructions,
        ];
    }
}
