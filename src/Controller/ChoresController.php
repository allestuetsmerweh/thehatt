<?php

namespace App\Controller;

use App\Service\FlatasticApi;
use App\Service\ImagePainter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ChoresController extends AbstractController
{
    #[Route('/chores')]
    public function list(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
    ): JsonResponse {
        $auth_data = $api->authenticateFromRequest($request);

        $flatmates = $api->getFlatmates();
        $chores = $api->getChores();

        return new JsonResponse([
            'flatmates'=> $flatmates,
            'chores'=> $chores,
        ]);
    }

    #[Route('/chores/table/{frequency}.{format}')]
    public function table(
        Request $request,
        FlatasticApi $api,
        LoggerInterface $logger,
        ImagePainter $painter,
        string $frequency,
        string $format,
    ): Response {
        $rotation_time = $this->parseFrequency($frequency);
        $table = $this->getTableForChores(
            $request, $api, 
            function ($chore) use ($rotation_time) {
                return $chore['rotationTime'] == $rotation_time;
            },
        );
        $image = $painter->planSimpleTable($table);
        return $painter->getImageResponse($image, $format);
    }

    private function parseFrequency(string $input_frequency) {
        $mappings = [
            'weekly' => '604800',
            'daily' => '86400',
            'on_demand' => '-1',
            'as_needed' => '-1',
        ];
        $frequency = $mappings[$input_frequency] ?? null;
        if ($frequency === null) {
            $frequency = $input_frequency;
        }
        return $frequency;
    }

    private function getTableForChores(
        Request $request,
        FlatasticApi $api,
        $chore_filter_fn,
    ) {
        $auth_data = $api->authenticateFromRequest($request);

        $flatmates = $api->getFlatmates();
        $chores = $api->getChores();

        $filtered_chores = [];
        foreach ($chores as $id => $chore) {
            if ($chore_filter_fn($chore)) {
                $filtered_chores[] = $chore;
            }
        }
        usort($filtered_chores, function ($a, $b) {
            return strcmp($a['title'], $b['title']);
        });
        $table_data = [];
        foreach ($filtered_chores as $chore) {
            $current_flatmate = $flatmates[strval($chore['currentUser'])];
            $days = number_format(abs($chore['timeLeftNext']) / 86400, 1, '.', '');
            $row = [
                $chore['title'],
                $current_flatmate['firstName'],
                $chore['timeLeftNext'] > 0 ? "in {$days} days" : "{$days} days ago",
            ];
            foreach ($row as $index => $value) {
                $col_widths[$index] = max($col_widths[$index] ?? 0, strlen($value));
            }
            $table_data[] = $row;
        }
        return $table_data;
    }
}
