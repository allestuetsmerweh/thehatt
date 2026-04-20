<?php

namespace App\Velo\Controller;

use App\Entity\Velo\BikeCountStation;
use App\Entity\Velo\BikeCountStatistic;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VeloController extends AbstractController {
    #[Route('/velo')]
    public function index(
        Request $request,
        LoggerInterface $logger,
    ): Response {
        $text = <<<'ZZZZZZZZZZ'
            <h1>velo</h1>
            ZZZZZZZZZZ;
        return new Response("{$text}");
    }

    #[Route('/velo/station/{id}', requirements: ['id' => '\d+'])]
    public function stationId(
        Request $request,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        int $id,
    ): Response {
        $station_repo = $em->getRepository(BikeCountStation::class);
        $station = $station_repo->findOneBy(['id' => $id]);
        if (!$station) {
            return new Response('Not found', 404);
        }
        $lat = $station->getPositionLatitude();
        $lng = $station->getPositionLongitude();
        $corr = cos($lat * pi() / 180);
        $radius = 0.0001;
        $similar_stations = $station_repo->matching(Criteria::create()
            ->where(Criteria::expr()->andX(
                Criteria::expr()->gt('position_latitude', $lat - $radius * $corr),
                Criteria::expr()->lt('position_latitude', $lat + $radius * $corr),
                Criteria::expr()->gt('position_longitude', $lng - $radius),
                Criteria::expr()->lt('position_longitude', $lng + $radius),
            ))
            ->setFirstResult(0)
            ->setMaxResults(1000));
        $text = <<<ZZZZZZZZZZ
            <h1>{$station->getName()} ({$station->getId()})</h1>
            <div>{$station->getOperatedSince()?->format('Y-m-d H:i:s')} &ndash; {$station->getOperatedUntil()?->format('Y-m-d H:i:s')}</div>
            ZZZZZZZZZZ;
        foreach ($similar_stations as $similar_station) {
            $text .= "<div>{$similar_station->getName()} ({$similar_station->getId()})</div>";
        }
        return new Response("{$text}");
    }

    #[Route('/velo/station_{ids}_history_yearly.json', requirements: ['ids' => '[\d,]+'])]
    public function stationHistoryYearly(
        Request $request,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        string $ids,
    ): Response {
        $station_repo = $em->getRepository(BikeCountStation::class);
        $stations = [];
        foreach (explode(',', $ids) as $raw_id) {
            $station = $station_repo->findOneBy(['id' => intval($raw_id)]);
            if (!$station) {
                return new Response('Not found', 404);
            }
            $stations[] = $station;
        }

        $stat_repo = $em->getRepository(BikeCountStatistic::class);
        $stats = $stat_repo->matching(Criteria::create()
            ->where(Criteria::expr()->andX(
                Criteria::expr()->in('station', $stations),
                Criteria::expr()->eq('month', -1),
                Criteria::expr()->eq('day', -1),
                Criteria::expr()->eq('hour', -1),
                Criteria::expr()->eq('kind', -1),
            ))
            ->setFirstResult(0)
            ->setMaxResults(1000));
        $data_by_year = [];
        $names = [];
        foreach ($stats as $stat) {
            $year = "{$stat->getYear()}";
            $station_id = "{$stat->getStation()->getId()}";
            $data_by_year[$year] ??= [];
            $data_by_year[$year][$station_id] = $stat->getCount();
            $names[$station_id] ??= "{$stat->getStation()->getName()} ({$station_id})";
        }

        $years = array_keys($data_by_year);
        usort($years, fn ($a, $b) => intval($a) <=> intval($b));
        $data = [];
        foreach ($years as $year) {
            $data_in_year = $data_by_year[$year];
            $data_in_year['year'] = $year;
            $data[] = $data_in_year;
        }
        $names['year'] = "Jahr";

        return new JsonResponse(['data' => $data, 'names' => $names], 200);
    }
}
